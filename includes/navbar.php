<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/notification.php";

$base_url = '/siakad-atitb';

$page_title = $page_title ?? 'Dashboard';
$page_subtitle = $page_subtitle ?? 'Sistem Informasi Akademik ATITB';  

$role = $_SESSION['role'] ?? 'pengguna';
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$foto_pengguna = null;

$jumlah_notifikasi = 0;
$link_notifikasi = $base_url . '/admin/notifikasi/data_notifikasi.php';
$link_profil = $base_url . '/admin/profil/profil.php';

if ($role === 'mahasiswa') {
    $link_notifikasi = $base_url . '/mahasiswa/notifikasi/data_notifikasi.php';
    $link_profil = $base_url . '/mahasiswa/profil.php';
} elseif ($role === 'dosen') {
    $link_notifikasi = $base_url . '/dosen/notifikasi/data_notifikasi.php';
    $link_profil = $base_url . '/dosen/profil.php';
} elseif ($role === 'kaprodi') {
    $link_notifikasi = $base_url . '/kaprodi/notifikasi/data_notifikasi.php';
    $link_profil = $base_url . '/admin/profil/profil.php';
} elseif ($role === 'pimpinan') {
    $link_notifikasi = $base_url . '/pimpinan/notifikasi/data_notifikasi.php';
    $link_profil = $base_url . '/admin/profil/profil.php';
}

if (isset($conn) && isset($_SESSION['id_user'])) {
    $id_user_navbar = intval($_SESSION['id_user']);
    $jumlah_notifikasi = jumlah_notifikasi_belum_dibaca($conn, $id_user_navbar);

    $qFotoNavbar = mysqli_query($conn, "
        SELECT foto
        FROM users
        WHERE id_user = '$id_user_navbar'
        LIMIT 1
    ");

    if ($qFotoNavbar && mysqli_num_rows($qFotoNavbar) > 0) {
        $dFotoNavbar = mysqli_fetch_assoc($qFotoNavbar);
        $nama_file_foto = trim((string) ($dFotoNavbar['foto'] ?? ''));
        $path_file_foto = __DIR__ . "/../uploads/pengguna/" . $nama_file_foto;

        if ($nama_file_foto !== '' && file_exists($path_file_foto)) {
            $foto_pengguna = $base_url . "/uploads/pengguna/" . rawurlencode($nama_file_foto);
        }
    }
}
?>

<header class="sticky top-0 z-30 bg-white border-b border-slate-200 shadow-sm lg:ml-[270px]">
    <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between gap-4">

        <div class="flex items-center gap-4 min-w-0">

            <button id="menuButton"
                class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                <i class="fa-solid fa-bars text-lg"></i>
            </button>

            <div class="min-w-0">
                <h1 class="text-lg sm:text-xl font-bold text-blue-800 truncate">
                    <?= htmlspecialchars($page_title); ?>
                </h1>
                <p class="hidden sm:block text-sm text-slate-500 truncate">
                    <?= htmlspecialchars($page_subtitle); ?>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">

            <a href="<?= $link_notifikasi; ?>"
                class="relative inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-700 transition">
                <i class="fa-solid fa-bell"></i>

                <?php if ($jumlah_notifikasi > 0): ?>
                    <span class="absolute -top-1 -right-1 min-w-5 h-5 px-1 bg-red-600 text-white text-[11px] font-bold flex items-center justify-center rounded-full">
                        <?= $jumlah_notifikasi > 99 ? '99+' : $jumlah_notifikasi; ?>
                    </span>
                <?php endif; ?>
            </a>

            <div class="relative">
                <button id="profileButton"
                    class="flex items-center gap-3 rounded-xl hover:bg-slate-100 px-2 py-2 transition">

                    <div class="hidden md:block text-right">
                        <p class="text-xs text-slate-400">Login sebagai</p>
                        <h3 class="text-sm font-bold text-slate-700 max-w-[180px] truncate">
                            <?= htmlspecialchars($nama_lengkap); ?>
                        </h3>
                        <p class="text-xs text-blue-600 capitalize">
                            <?= str_replace('_', ' ', htmlspecialchars($role)); ?>
                        </p>
                    </div>

                    <?php if ($foto_pengguna): ?>
                        <img src="<?= htmlspecialchars($foto_pengguna); ?>" alt="Foto <?= htmlspecialchars($nama_lengkap); ?>"
                            class="w-10 h-10 rounded-full object-cover border-2 border-blue-100 shadow-sm">
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-full bg-blue-700 text-white flex items-center justify-center font-bold">
                            <?= strtoupper(substr($nama_lengkap, 0, 1)); ?>
                        </div>
                    <?php endif; ?>

                    <i class="fa-solid fa-chevron-down text-xs text-slate-500 hidden sm:block"></i>
                </button>

                <div id="profileDropdown"
                    class="hidden absolute right-0 mt-3 w-56 bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden z-50">

                    <div class="px-4 py-3 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <?php if ($foto_pengguna): ?>
                                <img src="<?= htmlspecialchars($foto_pengguna); ?>" alt="Foto <?= htmlspecialchars($nama_lengkap); ?>"
                                    class="w-10 h-10 rounded-full object-cover border border-slate-200">
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-full bg-blue-700 text-white flex items-center justify-center font-bold">
                                    <?= strtoupper(substr($nama_lengkap, 0, 1)); ?>
                                </div>
                            <?php endif; ?>

                            <div class="min-w-0">
                                <p class="font-semibold text-slate-800 truncate">
                                    <?= htmlspecialchars($nama_lengkap); ?>
                                </p>
                                <p class="text-xs text-slate-500 capitalize">
                                    <?= str_replace('_', ' ', htmlspecialchars($role)); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <a href="<?= $link_profil; ?>"
                        class="flex items-center gap-3 px-4 py-3 text-slate-700 hover:bg-slate-50 transition">
                        <i class="fa-solid fa-user"></i>
                        <span>Profil Saya</span>
                    </a>

                    <form action="<?= $base_url; ?>/auth/logout.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_logout_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit"
                            class="w-full flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 transition text-left">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const menuButton = document.getElementById('menuButton');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (menuButton && sidebar && sidebarOverlay) {
        menuButton.addEventListener('click', function () {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('hidden');
        });

        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        });
    }

    const profileButton = document.getElementById('profileButton');
    const profileDropdown = document.getElementById('profileDropdown');

    if (profileButton && profileDropdown) {
        profileButton.addEventListener('click', function (e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', function () {
            profileDropdown.classList.add('hidden');
        });
    }
});
</script>
