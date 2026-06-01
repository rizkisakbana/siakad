<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/pengguna_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Role Akses";
$page_subtitle = "Monitoring daftar role pengguna sistem";

$data_role = pengguna_all($conn, "
    SELECT roles.*, COUNT(users.id_user) AS total_user
    FROM roles
    LEFT JOIN users ON roles.id_role = users.id_role
    GROUP BY roles.id_role
    ORDER BY roles.id_role ASC
");

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Role Akses Sistem</h2>
                <p class="text-sm text-slate-500 mt-1">Daftar role yang digunakan pada SIAKAD.</p>
            </div>

            <a href="data_pengguna.php" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-left w-16">No</th>
                        <th class="px-4 py-3 text-left">Role</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Keterangan</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Total User</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Tanggal Dibuat</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php $no = 1; foreach ($data_role as $row): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3"><?= $no++; ?></td>

                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800 capitalize">
                                    <?= htmlspecialchars(str_replace('_', ' ', $row['nama_role'])); ?>
                                </div>
                                <div class="lg:hidden text-xs text-slate-500 mt-1">
                                    <?= number_format($row['total_user']); ?> pengguna
                                </div>
                            </td>

                            <td class="px-4 py-3 hidden lg:table-cell">
                                <?= htmlspecialchars($row['keterangan'] ?? '-'); ?>
                            </td>

                            <td class="px-4 py-3 hidden lg:table-cell">
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                                    <?= number_format($row['total_user']); ?> User
                                </span>
                            </td>

                            <td class="px-4 py-3 hidden lg:table-cell">
                                <?= tanggal_jam_indonesia($row['created_at']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
