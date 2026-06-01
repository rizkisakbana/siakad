<?php
require_once "../includes/session.php";

kirim_header_no_cache();

if (isset($_SESSION['id_user'])) {
    if ($_SESSION['role'] == 'super_admin' || $_SESSION['role'] == 'admin_akademik') {
        header("Location: ../admin/dashboard.php");
    } elseif ($_SESSION['role'] == 'mahasiswa') {
        header("Location: ../mahasiswa/dashboard.php");
    } elseif ($_SESSION['role'] == 'dosen') {
        header("Location: ../dosen/dashboard.php");
    } elseif ($_SESSION['role'] == 'kaprodi') {
        header("Location: ../kaprodi/dashboard.php");
    } elseif ($_SESSION['role'] == 'pimpinan') {
        header("Location: ../pimpinan/dashboard.php");
    }
    exit;
}

$error = $_GET['error'] ?? '';
$info = $_GET['info'] ?? '';
$pesan_error = "";
$pesan_info = "";

if ($error !== '') {
    $pesan_error = "Username atau password salah.";
    if ($error === 'database') {
        $pesan_error = "Database sedang bermasalah. Silakan periksa layanan MySQL/XAMPP.";
    } elseif ($error === 'nonaktif') {
        $pesan_error = "Akun pengguna tidak aktif.";
    } elseif ($error === 'role') {
        $pesan_error = "Role pengguna belum dikenali sistem.";
    } elseif ($error === 'session') {
        $pesan_error = "Sesi tidak valid. Silakan login ulang.";
    }
}

if ($info === 'reset_sent') {
    $pesan_info = "Jika email terdaftar, tautan reset password telah dikirim.";
} elseif ($info === 'password_reset') {
    $pesan_info = "Password berhasil diperbarui. Silakan login menggunakan password baru.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SIAKAD ATITB</title>
    <link rel="icon" type="image/x-icon" href="/siakad-atitb/assets/img/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">

    <main class="min-h-screen grid grid-cols-1 lg:grid-cols-[1.08fr_0.92fr]">
        <section class="relative hidden overflow-hidden bg-blue-950 lg:flex">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(34,211,238,0.22),transparent_30%),radial-gradient(circle_at_80%_10%,rgba(16,185,129,0.20),transparent_28%),linear-gradient(135deg,#172554_0%,#075985_58%,#0f766e_100%)]"></div>
            <div class="relative z-10 flex min-h-screen w-full flex-col justify-between p-10 xl:p-12 text-white">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 rounded-2xl bg-white p-2 shadow-lg">
                        <img src="/siakad-atitb/assets/img/logo-atitb.png" alt="Logo ATITB" class="h-full w-full object-contain">
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-cyan-100">Akademi Teknik Informatika Tunas Bangsa</p>
                        <h1 class="text-2xl font-black tracking-tight">SIAKAD ATITB</h1>
                    </div>
                </div>

                <div class="max-w-2xl">
                    <div class="mb-6 inline-flex items-center rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold backdrop-blur">
                        <i class="fa-solid fa-shield-halved mr-2 text-emerald-300"></i>
                        Sistem akademik internal dan integrasi NeoFeeder
                    </div>
                    <h2 class="text-4xl font-black leading-tight xl:text-5xl">
                        Satu pintu untuk data akademik yang rapi, aman, dan siap pelaporan.
                    </h2>
                    <p class="mt-5 max-w-xl text-base leading-7 text-blue-50">
                        Kelola mahasiswa, dosen, perkuliahan, nilai, dan pelaporan PDDikti dalam pengalaman kerja yang lebih terstruktur.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                        <p class="text-2xl font-black">D3</p>
                        <p class="mt-1 text-xs font-semibold text-blue-100">Program vokasi</p>
                    </div>
                    <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                        <p class="text-2xl font-black">24/7</p>
                        <p class="mt-1 text-xs font-semibold text-blue-100">Akses sistem</p>
                    </div>
                    <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                        <p class="text-2xl font-black">PDDikti</p>
                        <p class="mt-1 text-xs font-semibold text-blue-100">NeoFeeder-ready</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="flex min-h-screen items-center justify-center p-4 sm:p-6 lg:p-10">
            <div class="w-full max-w-md">
                <div class="mb-8 flex items-center gap-4 lg:hidden">
                    <div class="h-14 w-14 rounded-2xl bg-white p-2 shadow">
                        <img src="/siakad-atitb/assets/img/logo-atitb.png" alt="Logo ATITB" class="h-full w-full object-contain">
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-blue-900">SIAKAD ATITB</h1>
                        <p class="text-sm text-slate-500">Sistem Informasi Akademik</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl sm:p-8">
                    <div class="mb-7">
                        <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Selamat Datang</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-900">Masuk ke akun Anda</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Gunakan username dan password yang terdaftar di SIAKAD.
                        </p>
                    </div>

                    <?php if ($pesan_error !== '') : ?>
                        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                            <i class="fa-solid fa-circle-exclamation mr-2"></i>
                            <?= htmlspecialchars($pesan_error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($pesan_info !== '') : ?>
                        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
                            <i class="fa-solid fa-circle-check mr-2"></i>
                            <?= htmlspecialchars($pesan_info) ?>
                        </div>
                    <?php endif; ?>

                    <form action="login_proses.php" method="POST" class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Username</label>
                            <div class="relative">
                                <i class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" name="username" required autocomplete="username"
                                    class="w-full rounded-xl border border-slate-300 py-3 pl-11 pr-4 focus:ring-2 focus:ring-blue-600 outline-none"
                                    placeholder="Masukkan username">
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label class="block text-sm font-semibold text-slate-700">Password</label>
                                <a href="forgot_password.php" class="text-sm font-bold text-blue-700 hover:text-blue-900">
                                    Lupa password?
                                </a>
                            </div>
                            <div class="relative">
                                <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="password" name="password" required autocomplete="current-password"
                                    class="w-full rounded-xl border border-slate-300 py-3 pl-11 pr-4 focus:ring-2 focus:ring-blue-600 outline-none"
                                    placeholder="Masukkan password">
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full rounded-xl bg-blue-700 px-5 py-3 font-bold text-white shadow-lg shadow-blue-700/20 transition hover:bg-blue-800">
                            <i class="fa-solid fa-right-to-bracket mr-2"></i>
                            Login
                        </button>
                    </form>
                </div>

                <p class="mt-6 text-center text-xs leading-6 text-slate-500">
                    © <?= date('Y'); ?> Akademi Teknik Informatika Tunas Bangsa. Akses hanya untuk pengguna terdaftar.
                </p>
            </div>
        </section>
    </main>

</body>
</html>
