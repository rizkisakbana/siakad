<?php
require_once "../includes/session.php";
require_once "../config/database.php";

kirim_header_no_cache();

if (isset($_SESSION['id_user'])) {
    header("Location: ../admin/dashboard.php");
    exit;
}

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$token = trim($token);
$pesan_status = "";
$tipe_status = "";
$reset_valid = false;
$reset_data = null;

if ($token !== '') {
    $token_hash = hash('sha256', $token);
    $token_hash_db = mysqli_real_escape_string($conn, $token_hash);

    $query = mysqli_query($conn, "
        SELECT password_reset_tokens.*, users.nama_lengkap, users.status
        FROM password_reset_tokens
        INNER JOIN users ON password_reset_tokens.id_user = users.id_user
        WHERE password_reset_tokens.token_hash = '$token_hash_db'
        AND password_reset_tokens.used_at IS NULL
        AND password_reset_tokens.expires_at >= NOW()
        LIMIT 1
    ");

    if ($query && mysqli_num_rows($query) === 1) {
        $reset_valid = true;
        $reset_data = mysqli_fetch_assoc($query);
    }
}

if ($token === '' || !$reset_valid) {
    $pesan_status = "Link reset password tidak valid atau sudah kedaluwarsa.";
    $tipe_status = "error";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset_valid) {
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    if (strlen($password_baru) < 8) {
        $pesan_status = "Password baru minimal 8 karakter.";
        $tipe_status = "error";
    } elseif ($password_baru !== $konfirmasi_password) {
        $pesan_status = "Konfirmasi password tidak sesuai.";
        $tipe_status = "error";
    } elseif (($reset_data['status'] ?? '') !== 'aktif') {
        $pesan_status = "Akun tidak aktif. Silakan hubungi administrator.";
        $tipe_status = "error";
    } else {
        $id_user = intval($reset_data['id_user']);
        $id_reset = intval($reset_data['id_reset']);
        $password_hash = mysqli_real_escape_string($conn, password_hash($password_baru, PASSWORD_DEFAULT));

        mysqli_begin_transaction($conn);

        $update_user = mysqli_query($conn, "
            UPDATE users
            SET password = '$password_hash'
            WHERE id_user = '$id_user'
        ");

        $update_token = mysqli_query($conn, "
            UPDATE password_reset_tokens
            SET used_at = NOW()
            WHERE id_reset = '$id_reset'
        ");

        if ($update_user && $update_token) {
            mysqli_commit($conn);
            header("Location: login.php?info=password_reset");
            exit;
        }

        mysqli_rollback($conn);
        $pesan_status = "Password gagal diperbarui. Silakan coba beberapa saat lagi.";
        $tipe_status = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SIAKAD ATITB</title>
    <link rel="icon" type="image/x-icon" href="/siakad-atitb/assets/img/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <main class="min-h-screen flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-md">
            <a href="login.php" class="mb-6 inline-flex items-center text-sm font-bold text-blue-700 hover:text-blue-900">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Kembali ke login
            </a>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl sm:p-8">
                <div class="mb-7">
                    <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-blue-700">
                        <i class="fa-solid fa-lock-open text-2xl"></i>
                    </div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Password Baru</p>
                    <h1 class="mt-2 text-2xl font-black text-slate-900">Reset password akun</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Buat password baru yang kuat dan mudah Anda ingat.
                    </p>
                </div>

                <?php if ($pesan_status !== ''): ?>
                    <div class="mb-4 rounded-xl border px-4 py-3 text-sm font-semibold <?= $tipe_status === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-green-200 bg-green-50 text-green-700'; ?>">
                        <i class="fa-solid <?= $tipe_status === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'; ?> mr-2"></i>
                        <?= htmlspecialchars($pesan_status); ?>
                    </div>
                <?php endif; ?>

                <?php if ($reset_valid): ?>
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Password Baru</label>
                            <div class="relative">
                                <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="password" name="password_baru" required autocomplete="new-password"
                                    class="w-full rounded-xl border border-slate-300 py-3 pl-11 pr-4 focus:ring-2 focus:ring-blue-600 outline-none"
                                    placeholder="Minimal 8 karakter">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Konfirmasi Password</label>
                            <div class="relative">
                                <i class="fa-solid fa-shield-halved absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="password" name="konfirmasi_password" required autocomplete="new-password"
                                    class="w-full rounded-xl border border-slate-300 py-3 pl-11 pr-4 focus:ring-2 focus:ring-blue-600 outline-none"
                                    placeholder="Ulangi password baru">
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full rounded-xl bg-blue-700 px-5 py-3 font-bold text-white shadow-lg shadow-blue-700/20 transition hover:bg-blue-800">
                            <i class="fa-solid fa-check mr-2"></i>
                            Simpan Password Baru
                        </button>
                    </form>
                <?php else: ?>
                    <a href="forgot_password.php"
                        class="mt-2 inline-flex w-full items-center justify-center rounded-xl bg-blue-700 px-5 py-3 font-bold text-white shadow-lg shadow-blue-700/20 transition hover:bg-blue-800">
                        <i class="fa-solid fa-paper-plane mr-2"></i>
                        Minta Link Baru
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
