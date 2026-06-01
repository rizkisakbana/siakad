<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Detail Dosen";
$page_subtitle = "Informasi lengkap data dosen";

$id_dosen = intval($_GET['id'] ?? 0);

if ($id_dosen <= 0) {
    set_alert("error", "ID dosen tidak valid.");
    header("Location: data_dosen.php");
    exit;
}

$query = mysqli_query($conn, "
    SELECT 
        dosen.*,
        users.username,
        users.status AS status_user,
        users.last_login,
        roles.nama_role,
        prodi.kode_prodi,
        prodi.nama_prodi,
        prodi.jenjang,
        prodi.gelar
    FROM dosen
    LEFT JOIN users ON dosen.id_user = users.id_user
    LEFT JOIN roles ON users.id_role = roles.id_role
    LEFT JOIN prodi ON dosen.id_prodi = prodi.id_prodi
    WHERE dosen.id_dosen = '$id_dosen'
    LIMIT 1
");

if (mysqli_num_rows($query) < 1) {
    set_alert("error", "Data dosen tidak ditemukan.");
    header("Location: data_dosen.php");
    exit;
}

$data = mysqli_fetch_assoc($query);

$total_jadwal = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM jadwal_kuliah 
    WHERE id_dosen = '$id_dosen'
"))['total'] ?? 0;

$total_kelas = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(DISTINCT id_kelas) AS total 
    FROM jadwal_kuliah 
    WHERE id_dosen = '$id_dosen'
"))['total'] ?? 0;

$total_mk = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(DISTINCT id_mk) AS total 
    FROM jadwal_kuliah 
    WHERE id_dosen = '$id_dosen'
"))['total'] ?? 0;

$total_penugasan = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM dosen_penugasan_feeder
    WHERE id_dosen = '$id_dosen'
       OR id_dosen_feeder = '" . mysqli_real_escape_string($conn, $data['id_dosen_feeder'] ?: ($data['id_feeder'] ?? '')) . "'
"))['total'] ?? 0;

$nama_lengkap_dosen = trim(($data['gelar_depan'] ?? '') . ' ' . $data['nama_dosen'] . ' ' . ($data['gelar_belakang'] ?? ''));

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Melihat detail dosen: " . $data['nama_dosen'],
    "Dosen"
);

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Detail Dosen</h2>
            <p class="text-sm text-slate-500">Menampilkan informasi lengkap data dosen.</p>
        </div>

        <a href="data_dosen.php"
           class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div> -->

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Jadwal</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($total_jadwal); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Kelas</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($total_kelas); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Mata Kuliah Diampu</p>
            <h2 class="text-3xl font-bold text-purple-700 mt-2"><?= number_format($total_mk); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Penugasan PDDikti</p>
            <h2 class="text-3xl font-bold text-orange-700 mt-2">
                <?= number_format($total_penugasan); ?>
            </h2>
        </div>

    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <section class="lg:col-span-2 space-y-6">

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

                <div class="flex items-start gap-4 mb-6">
                    <?php if (!empty($data['foto'])): ?>
                        <img src="../../uploads/dosen/<?= htmlspecialchars($data['foto']); ?>"
                             alt="Foto Dosen"
                             class="w-20 h-20 rounded-2xl object-cover border border-slate-200">
                    <?php else: ?>
                        <div class="w-20 h-20 rounded-2xl bg-blue-700 text-white flex items-center justify-center text-2xl font-bold">
                            <?= strtoupper(substr($data['nama_dosen'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <h3 class="text-xl font-bold text-slate-800">
                            <?= htmlspecialchars($nama_lengkap_dosen); ?>
                        </h3>
                        <p class="text-sm text-slate-500 mt-1">
                            <?= htmlspecialchars($data['nidn'] ?? '-'); ?> • <?= htmlspecialchars($data['nama_prodi'] ?? '-'); ?>
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="px-3 py-1 rounded-full text-xs font-bold <?= ($data['status'] ?? '') == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                <?= htmlspecialchars($data['status'] ?? '-'); ?>
                            </span>

                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 capitalize">
                                <?= htmlspecialchars($data['status_dosen'] ?? '-'); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <h3 class="text-lg font-bold text-slate-800 mb-4">Identitas Dosen</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">ID Dosen</p>
                        <p class="font-semibold text-slate-800"><?= $data['id_dosen']; ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">ID User</p>
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['id_user'] ?? '-'); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">NIDN</p>
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['nidn'] ?? '-'); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">NIDK</p>
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['nidk'] ?? '-'); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">NUPTK</p>
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['nuptk'] ?? '-'); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">NIP</p>
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['nip'] ?? '-'); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 sm:col-span-2">
                        <p class="text-xs text-slate-500 mb-1">ID Dosen NeoFeeder</p>
                        <p class="font-semibold text-slate-800 break-all"><?= htmlspecialchars($data['id_dosen_feeder'] ?: ($data['id_feeder'] ?? '-')); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Gelar Depan</p>
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['gelar_depan'] ?? '-'); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Gelar Belakang</p>
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['gelar_belakang'] ?? '-'); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 sm:col-span-2">
                        <p class="text-xs text-slate-500 mb-1">Nama Lengkap</p>
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($nama_lengkap_dosen); ?></p>
                    </div>

                </div>

            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Biodata Dosen</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Jenis Kelamin</p>
                        <p class="font-semibold text-slate-800">
                            <?= ($data['jenis_kelamin'] ?? '') == 'L' ? 'Laki-laki' : (($data['jenis_kelamin'] ?? '') == 'P' ? 'Perempuan' : '-'); ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Tempat Lahir</p>
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['tempat_lahir'] ?? '-'); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Tanggal Lahir</p>
                        <p class="font-semibold text-slate-800">
                            <?= !empty($data['tanggal_lahir']) ? tanggal_indonesia($data['tanggal_lahir']) : '-'; ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Status Dosen</p>
                        <p class="font-semibold text-slate-800 capitalize"><?= htmlspecialchars($data['status_dosen'] ?? '-'); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Agama PDDikti</p>
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['agama'] ?: ($data['id_agama_feeder'] ?? '-')); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Status Aktif PDDikti</p>
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['nama_status_aktif'] ?: ($data['id_status_aktif_feeder'] ?? '-')); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Ikatan Kerja PDDikti</p>
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['nama_ikatan_kerja'] ?: ($data['id_ikatan_kerja_feeder'] ?? '-')); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 sm:col-span-2">
                        <p class="text-xs text-slate-500 mb-1">Alamat</p>
                        <p class="font-semibold text-slate-800 whitespace-pre-line"><?= htmlspecialchars($data['alamat'] ?? '-'); ?></p>
                    </div>

                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Kontak Dosen</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">Email</p>
                        <p class="font-semibold text-slate-800 break-all"><?= htmlspecialchars($data['email'] ?? '-'); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 mb-1">No. HP / WhatsApp</p>
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['no_hp'] ?? '-'); ?></p>
                    </div>

                </div>
            </div>

        </section>

        <aside class="space-y-6">

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Program Studi</h3>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4 border-b pb-3">
                        <span class="text-slate-500">Kode Prodi</span>
                        <span class="font-semibold text-slate-800 text-right"><?= htmlspecialchars($data['kode_prodi'] ?? '-'); ?></span>
                    </div>

                    <div class="flex justify-between gap-4 border-b pb-3">
                        <span class="text-slate-500">Nama Prodi</span>
                        <span class="font-semibold text-slate-800 text-right"><?= htmlspecialchars($data['nama_prodi'] ?? '-'); ?></span>
                    </div>

                    <div class="flex justify-between gap-4 border-b pb-3">
                        <span class="text-slate-500">Jenjang</span>
                        <span class="font-semibold text-slate-800 text-right"><?= htmlspecialchars($data['jenjang'] ?? '-'); ?></span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Gelar Prodi</span>
                        <span class="font-semibold text-slate-800 text-right"><?= htmlspecialchars($data['gelar'] ?? '-'); ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Akun Pengguna</h3>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4 border-b pb-3">
                        <span class="text-slate-500">Username</span>
                        <span class="font-semibold text-slate-800 text-right"><?= htmlspecialchars($data['username'] ?? '-'); ?></span>
                    </div>

                    <div class="flex justify-between gap-4 border-b pb-3">
                        <span class="text-slate-500">Role</span>
                        <span class="font-semibold text-slate-800 text-right capitalize"><?= htmlspecialchars(str_replace('_', ' ', $data['nama_role'] ?? '-')); ?></span>
                    </div>

                    <div class="flex justify-between gap-4 border-b pb-3">
                        <span class="text-slate-500">Status Akun</span>
                        <span class="font-semibold text-slate-800 text-right"><?= htmlspecialchars($data['status_user'] ?? '-'); ?></span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Terakhir Login</span>
                        <span class="font-semibold text-slate-800 text-right">
                            <?= !empty($data['last_login']) ? tanggal_jam_indonesia($data['last_login']) : '-'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Riwayat Sistem</h3>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4 border-b pb-3">
                        <span class="text-slate-500">Dibuat</span>
                        <span class="font-semibold text-slate-800 text-right">
                            <?= !empty($data['created_at']) ? tanggal_jam_indonesia($data['created_at']) : '-'; ?>
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Diperbarui</span>
                        <span class="font-semibold text-slate-800 text-right">
                            <?= !empty($data['updated_at']) ? tanggal_jam_indonesia($data['updated_at']) : '-'; ?>
                        </span>
                    </div>

                    <div class="flex justify-between gap-4 border-t pt-3">
                        <span class="text-slate-500">Status Feeder</span>
                        <span class="font-semibold text-slate-800 text-right"><?= htmlspecialchars($data['status_sync_feeder'] ?? 'belum'); ?></span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Sync Terakhir</span>
                        <span class="font-semibold text-slate-800 text-right">
                            <?= !empty($data['last_sync_feeder']) ? tanggal_jam_indonesia($data['last_sync_feeder']) : '-'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Penugasan PDDikti</h3>

                <?php
                $q_penugasan = mysqli_query($conn, "
                    SELECT *
                    FROM dosen_penugasan_feeder
                    WHERE id_dosen = '$id_dosen'
                       OR id_dosen_feeder = '" . mysqli_real_escape_string($conn, $data['id_dosen_feeder'] ?: ($data['id_feeder'] ?? '')) . "'
                    ORDER BY id_tahun_ajaran_feeder DESC, mulai_surat_tugas DESC
                    LIMIT 5
                ");
                ?>

                <div class="space-y-3 text-sm">
                    <?php if ($q_penugasan && mysqli_num_rows($q_penugasan) > 0): ?>
                        <?php while ($p = mysqli_fetch_assoc($q_penugasan)): ?>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                <p class="font-bold text-slate-800"><?= htmlspecialchars($p['nama_program_studi'] ?? '-'); ?></p>
                                <p class="mt-1 text-xs text-slate-500">
                                    <?= htmlspecialchars($p['nama_tahun_ajaran'] ?? '-'); ?> •
                                    <?= ($p['is_homebase'] ?? '0') == '1' ? 'Homebase' : 'Non-homebase'; ?>
                                </p>
                                <p class="mt-2 text-xs text-slate-500">
                                    Surat: <?= htmlspecialchars($p['nomor_surat_tugas'] ?: '-'); ?>
                                </p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-sm text-slate-500">Belum ada data penugasan dari NeoFeeder.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Aksi Data</h3>

                <div class="space-y-3">
                    <a href="edit_dosen.php?id=<?= $data['id_dosen']; ?>"
                       class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-yellow-500 hover:bg-yellow-600 text-white font-semibold">
                        <i class="fa-solid fa-pen mr-2"></i>
                        Edit Dosen
                    </a>

                    <?php if ($total_jadwal < 1): ?>
                        <a href="hapus_dosen.php?id=<?= $data['id_dosen']; ?>"
                           onclick="return confirm('Yakin ingin menghapus data dosen ini?')"
                           class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">
                            <i class="fa-solid fa-trash mr-2"></i>
                            Hapus Dosen
                        </a>
                    <?php else: ?>
                        <button type="button"
                                onclick="alert('Dosen tidak dapat dihapus karena sudah digunakan pada jadwal kuliah.')"
                                class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-300 text-slate-600 font-semibold cursor-not-allowed">
                            <i class="fa-solid fa-lock mr-2"></i>
                            Tidak Bisa Dihapus
                        </button>
                    <?php endif; ?>

                    <a href="data_dosen.php"
                       class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Kembali
                    </a>
                </div>
            </div>

        </aside>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>
