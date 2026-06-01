<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/notifikasi_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$page_title = "Data Email Gateway";
$page_subtitle = "Monitoring riwayat pengiriman email sistem";

$keyword = mysqli_real_escape_string($conn, $_GET['keyword'] ?? '');

$where = "";

if (!empty($keyword)) {
    $where = "WHERE 
        users.nama_lengkap LIKE '%$keyword%' OR
        users.username LIKE '%$keyword%' OR
        email_log.tujuan_email LIKE '%$keyword%' OR
        email_log.subjek LIKE '%$keyword%' OR
        email_log.isi_pesan LIKE '%$keyword%' OR
        email_log.status LIKE '%$keyword%'
    ";
}

$total_email = notifikasi_count($conn, "
    SELECT COUNT(*) AS total FROM email_log
");

$total_terkirim = notifikasi_count($conn, "
    SELECT COUNT(*) AS total FROM email_log WHERE status = 'terkirim'
");

$total_pending = notifikasi_count($conn, "
    SELECT COUNT(*) AS total FROM email_log WHERE status = 'pending'
");

$total_gagal = notifikasi_count($conn, "
    SELECT COUNT(*) AS total FROM email_log WHERE status = 'gagal'
");

$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;

$total_data_filter = notifikasi_count($conn, "
    SELECT COUNT(*) AS total
    FROM email_log
    LEFT JOIN users ON email_log.id_user = users.id_user
    $where
");

$total_page = ceil($total_data_filter / $limit);

$data_email = notifikasi_all($conn, "
    SELECT 
        email_log.*,
        users.nama_lengkap,
        users.username
    FROM email_log
    LEFT JOIN users ON email_log.id_user = users.id_user
    $where
    ORDER BY email_log.id_email_log DESC
    LIMIT $limit OFFSET $offset
");

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Total Email</p>
                    <h2 class="text-3xl font-bold text-blue-700 mt-2">
                        <?= number_format($total_email); ?>
                    </h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-envelope text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Terkirim</p>
                    <h2 class="text-3xl font-bold text-green-700 mt-2">
                        <?= number_format($total_terkirim); ?>
                    </h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-green-100 text-green-700 flex items-center justify-center">
                    <i class="fa-solid fa-circle-check text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Pending</p>
                    <h2 class="text-3xl font-bold text-orange-700 mt-2">
                        <?= number_format($total_pending); ?>
                    </h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-orange-100 text-orange-700 flex items-center justify-center">
                    <i class="fa-solid fa-clock text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Gagal</p>
                    <h2 class="text-3xl font-bold text-red-700 mt-2">
                        <?= number_format($total_gagal); ?>
                    </h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-700 flex items-center justify-center">
                    <i class="fa-solid fa-circle-xmark text-xl"></i>
                </div>
            </div>
        </div>

    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6 mb-6">
        <div class="mb-5">
            <h2 class="text-xl font-bold text-slate-800">Menu Modul Notifikasi</h2>
            <p class="text-sm text-slate-500 mt-1">
                Akses cepat monitoring notifikasi, email, WhatsApp, dan test gateway.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

            <a href="data_notifikasi.php"
               class="p-5 rounded-2xl border border-slate-100 hover:border-blue-300 hover:bg-blue-50 transition">
                <div class="w-11 h-11 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center mb-4">
                    <i class="fa-solid fa-bell"></i>
                </div>
                <h3 class="font-bold text-slate-800">Data Notifikasi</h3>
                <p class="text-sm text-slate-500 mt-1">Pantau notifikasi internal sistem.</p>
            </a>

            <a href="data_email.php"
               class="p-5 rounded-2xl border border-purple-200 bg-purple-50 hover:bg-purple-100 transition">
                <div class="w-11 h-11 rounded-xl bg-purple-700 text-white flex items-center justify-center mb-4">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <h3 class="font-bold text-slate-800">Data Email</h3>
                <p class="text-sm text-slate-500 mt-1">Lihat riwayat email gateway.</p>
            </a>

            <a href="data_whatsapp.php"
               class="p-5 rounded-2xl border border-slate-100 hover:border-green-300 hover:bg-green-50 transition">
                <div class="w-11 h-11 rounded-xl bg-green-100 text-green-700 flex items-center justify-center mb-4">
                    <i class="fa-brands fa-whatsapp"></i>
                </div>
                <h3 class="font-bold text-slate-800">Data WhatsApp</h3>
                <p class="text-sm text-slate-500 mt-1">Lihat riwayat WA gateway.</p>
            </a>

            <a href="test_gateway.php"
               class="p-5 rounded-2xl border border-slate-100 hover:border-orange-300 hover:bg-orange-50 transition">
                <div class="w-11 h-11 rounded-xl bg-orange-100 text-orange-700 flex items-center justify-center mb-4">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
                <h3 class="font-bold text-slate-800">Test Gateway</h3>
                <p class="text-sm text-slate-500 mt-1">Uji email dan WhatsApp gateway.</p>
            </a>

        </div>
    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Riwayat Email Gateway</h2>
                <p class="text-sm text-slate-500 mt-1">
                    Menampilkan daftar email yang diproses oleh sistem.
                </p>
            </div>

            <form method="GET" class="w-full lg:w-auto">
                <input type="hidden" name="page" value="1">

                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="text"
                           name="keyword"
                           value="<?= htmlspecialchars($keyword); ?>"
                           placeholder="Cari email..."
                           class="w-full sm:w-72 rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

                    <button type="submit"
                            class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-search mr-2"></i>
                        Cari
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-left w-16">No</th>
                        <th class="px-4 py-3 text-left">Email Tujuan</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Pengguna</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Subjek</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Waktu</th>
                        <th class="px-4 py-3 text-center w-36">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($data_email)): ?>
                        <?php $no = $offset + 1; ?>
                        <?php foreach ($data_email as $row): ?>

                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <?= $no++; ?>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800 break-all">
                                        <?= htmlspecialchars($row['tujuan_email']); ?>
                                    </div>

                                    <div class="lg:hidden mt-2">
                                        <div class="text-xs font-semibold text-slate-700">
                                            <?= htmlspecialchars($row['subjek']); ?>
                                        </div>
                                        <div class="text-xs text-slate-400 mt-1">
                                            <?= tanggal_jam_indonesia($row['created_at']); ?>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <div class="font-semibold text-slate-800">
                                        <?= htmlspecialchars($row['nama_lengkap'] ?? '-'); ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <?= htmlspecialchars($row['username'] ?? '-'); ?>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <div class="font-semibold text-slate-800 max-w-sm truncate">
                                        <?= htmlspecialchars($row['subjek']); ?>
                                    </div>
                                    <div class="text-xs text-slate-500 max-w-md truncate">
                                        <?= strip_tags($row['isi_pesan']); ?>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold
                                        <?php
                                            if ($row['status'] == 'terkirim') echo 'bg-green-100 text-green-700';
                                            elseif ($row['status'] == 'pending') echo 'bg-orange-100 text-orange-700';
                                            else echo 'bg-red-100 text-red-700';
                                        ?>">
                                        <?= htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <div class="font-semibold text-slate-700">
                                        <?= tanggal_indonesia($row['created_at']); ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <?= jam_indonesia($row['created_at']); ?>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="detail_email.php?id=<?= $row['id_email_log']; ?>"
                                           class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-blue-100 hover:bg-blue-200 text-blue-700 transition"
                                           title="Detail">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>

                                        <a href="hapus_email.php?id=<?= $row['id_email_log']; ?>"
                                           onclick="return confirm('Yakin ingin menghapus log email ini?')"
                                           class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-red-100 hover:bg-red-200 text-red-700 transition"
                                           title="Hapus">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                Data email tidak ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_page > 1): ?>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-6">

                <div class="text-sm text-slate-500">
                    Menampilkan 
                    <span class="font-semibold text-slate-700"><?= $offset + 1; ?></span>
                    -
                    <span class="font-semibold text-slate-700">
                        <?= min($offset + $limit, $total_data_filter); ?>
                    </span>
                    dari 
                    <span class="font-semibold text-slate-700"><?= $total_data_filter; ?></span>
                    data
                </div>

                <div class="flex flex-wrap items-center gap-2">

                    <?php if ($page > 1): ?>
                        <a href="?keyword=<?= urlencode($keyword); ?>&page=<?= $page - 1; ?>"
                           class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm">
                            Sebelumnya
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_page; $i++): ?>
                        <?php if ($i == 1 || $i == $total_page || ($i >= $page - 2 && $i <= $page + 2)): ?>

                            <a href="?keyword=<?= urlencode($keyword); ?>&page=<?= $i; ?>"
                               class="px-4 py-2 rounded-xl text-sm font-semibold
                               <?= $i == $page 
                                    ? 'bg-blue-700 text-white' 
                                    : 'bg-slate-100 hover:bg-slate-200 text-slate-700'; ?>">
                                <?= $i; ?>
                            </a>

                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>

                            <span class="px-2 text-slate-400">...</span>

                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_page): ?>
                        <a href="?keyword=<?= urlencode($keyword); ?>&page=<?= $page + 1; ?>"
                           class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm">
                            Berikutnya
                        </a>
                    <?php endif; ?>

                </div>

            </div>
        <?php endif; ?>

    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
