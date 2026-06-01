<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../includes/notification.php";

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$page_title = "Detail Notifikasi";
$page_subtitle = "Informasi lengkap notifikasi internal sistem";

$id_notifikasi = intval($_GET['id'] ?? 0);

if ($id_notifikasi <= 0) {
    echo "<script>
        alert('ID notifikasi tidak valid.');
        window.location='data_notifikasi.php';
    </script>";
    exit;
}

$query = mysqli_query($conn, "
    SELECT 
        notifikasi.*,
        users.nama_lengkap,
        users.username,
        users.email,
        users.no_hp,
        roles.nama_role
    FROM notifikasi
    LEFT JOIN users ON notifikasi.id_user = users.id_user
    LEFT JOIN roles ON users.id_role = roles.id_role
    WHERE notifikasi.id_notifikasi = '$id_notifikasi'
    LIMIT 1
");

if (mysqli_num_rows($query) < 1) {
    echo "<script>
        alert('Data notifikasi tidak ditemukan.');
        window.location='data_notifikasi.php';
    </script>";
    exit;
}

$data = mysqli_fetch_assoc($query);

if ($data['status_baca'] === 'belum') {
    tandai_notifikasi_dibaca($conn, $id_notifikasi, $_SESSION['id_user'], true);
    $data['status_baca'] = 'sudah';
    $data['dibaca_pada'] = date('Y-m-d H:i:s');
}

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Melihat detail notifikasi ID: " . $id_notifikasi,
    "Notifikasi"
);

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Detail Notifikasi</h2>
            <p class="text-sm text-slate-500">Menampilkan informasi lengkap notifikasi internal.</p>
        </div>

        <a href="data_notifikasi.php"
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
                        if ($data['tipe'] == 'success') echo 'bg-green-100 text-green-700';
                        elseif ($data['tipe'] == 'warning') echo 'bg-yellow-100 text-yellow-700';
                        elseif ($data['tipe'] == 'danger') echo 'bg-red-100 text-red-700';
                        else echo 'bg-blue-100 text-blue-700';
                    ?> flex items-center justify-center">

                    <?php if ($data['tipe'] == 'success'): ?>
                        <i class="fa-solid fa-circle-check text-2xl"></i>
                    <?php elseif ($data['tipe'] == 'warning'): ?>
                        <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
                    <?php elseif ($data['tipe'] == 'danger'): ?>
                        <i class="fa-solid fa-circle-xmark text-2xl"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-circle-info text-2xl"></i>
                    <?php endif; ?>

                </div>

                <div>
                    <h3 class="text-lg font-bold text-slate-800">
                        <?= htmlspecialchars($data['judul']); ?>
                    </h3>
                    <p class="text-sm text-slate-500">
                        Tipe: <?= htmlspecialchars($data['tipe']); ?>
                    </p>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 mb-5">
                <p class="text-xs text-slate-500 mb-2">Isi Pesan</p>
                <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">
                    <?= htmlspecialchars($data['pesan']); ?>
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">ID Notifikasi</p>
                    <p class="font-semibold text-slate-800">
                        <?= $data['id_notifikasi']; ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Status Baca</p>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold 
                        <?= $data['status_baca'] == 'belum' ? 'bg-orange-100 text-orange-700' : 'bg-slate-200 text-slate-700'; ?>">
                        <?= htmlspecialchars($data['status_baca']); ?>
                    </span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Dibaca Pada</p>
                    <p class="font-semibold text-slate-800">
                        <?= !empty($data['dibaca_pada']) ? tanggal_jam_indonesia($data['dibaca_pada']) : '-'; ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Tanggal</p>
                    <p class="font-semibold text-slate-800">
                        <?= tanggal_indonesia($data['created_at']); ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Waktu</p>
                    <p class="font-semibold text-slate-800">
                        <?= jam_indonesia($data['created_at']); ?>
                    </p>
                </div>

            </div>

            <?php if (!empty($data['link'])): ?>
                <div class="mt-5 p-4 rounded-xl bg-blue-50 border border-blue-100">
                    <p class="text-xs text-blue-600 mb-2">Link Tujuan</p>
                    <a href="<?= htmlspecialchars($data['link']); ?>"
                       class="text-sm font-semibold text-blue-700 hover:underline break-all">
                        <?= htmlspecialchars($data['link']); ?>
                    </a>
                </div>
            <?php endif; ?>

        </section>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <h3 class="text-lg font-bold text-slate-800 mb-4">
                Penerima Notifikasi
            </h3>

            <div class="flex items-center gap-4 mb-5">
                <div class="w-14 h-14 rounded-full bg-blue-700 text-white flex items-center justify-center text-xl font-bold">
                    <?= strtoupper(substr($data['nama_lengkap'] ?? 'P', 0, 1)); ?>
                </div>

                <div>
                    <p class="font-bold text-slate-800">
                        <?= htmlspecialchars($data['nama_lengkap'] ?? '-'); ?>
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
                <a href="data_notifikasi.php"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Kembali
                </a>

                <a href="hapus_notifikasi.php?id=<?= $data['id_notifikasi']; ?>"
                   onclick="return confirm('Yakin ingin menghapus notifikasi ini?')"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">
                    <i class="fa-solid fa-trash mr-2"></i>
                    Hapus Notifikasi
                </a>
            </div>

        </aside>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>
