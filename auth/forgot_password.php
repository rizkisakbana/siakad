<?php
require_once "../includes/session.php";
require_once "../config/database.php";
require_once "../includes/email_gateway.php";

kirim_header_no_cache();

if (isset($_SESSION['id_user'])) {
    header("Location: ../admin/dashboard.php");
    exit;
}

$pesan_status = "";
$tipe_status = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pesan_status = "Jika email terdaftar, tautan reset password telah dikirim.";
    $tipe_status = "success";

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan_status = "Masukkan alamat email yang valid.";
        $tipe_status = "error";
    } else {
        $stmt = mysqli_prepare($conn, "
            SELECT id_user, nama_lengkap, email, status
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);

            if ($user && ($user['status'] ?? '') === 'aktif') {
                $id_user = intval($user['id_user']);
                $token = bin2hex(random_bytes(32));
                $token_hash = hash('sha256', $token);
                $email_db = mysqli_real_escape_string($conn, $user['email']);
                $token_hash_db = mysqli_real_escape_string($conn, $token_hash);
                $ip_db = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '');
                $agent_db = mysqli_real_escape_string($conn, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255));

                mysqli_query($conn, "
                    UPDATE password_reset_tokens
                    SET used_at = NOW()
                    WHERE id_user = '$id_user'
                    AND used_at IS NULL
                ");

                mysqli_query($conn, "
                    INSERT INTO password_reset_tokens
                    (id_user, email, token_hash, expires_at, request_ip, user_agent)
                    VALUES
                    ('$id_user', '$email_db', '$token_hash_db', DATE_ADD(NOW(), INTERVAL 60 MINUTE), '$ip_db', '$agent_db')
                ");

                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $reset_link = $scheme . '://' . $host . '/siakad-atitb/auth/reset_password.php?token=' . urlencode($token);
                $nama = htmlspecialchars($user['nama_lengkap'], ENT_QUOTES, 'UTF-8');

                $isi_email = "
                    <p>Yth. <strong>$nama</strong>,</p>
                    <p>Kami menerima permintaan reset password untuk akun SIAKAD ATITB Anda.</p>
                    <p>Silakan klik tombol berikut untuk membuat password baru. Tautan ini berlaku selama 60 menit.</p>
                    <p>
                        <a href='$reset_link' style='background:#1d4ed8;color:#ffffff;padding:12px 18px;border-radius:8px;text-decoration:none;display:inline-block;font-weight:bold;'>
                            Reset Password
                        </a>
                    </p>
                    <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
                    <p>Hormat kami,<br><strong>SIAKAD ATITB</strong></p>
                ";

                kirim_email($conn, $id_user, $user['email'], "Reset Password SIAKAD ATITB", $isi_email);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SIAKAD ATITB</title>
    <link rel="icon" type="image/x-icon" href="/siakad-atitb/assets/img/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <main class="min-h-screen grid grid-cols-1 lg:grid-cols-[0.92fr_1.08fr]">
        <section class="flex min-h-screen items-center justify-center p-4 sm:p-6 lg:p-10">
            <div class="w-full max-w-md">
                <a href="login.php" class="mb-6 inline-flex items-center text-sm font-bold text-blue-700 hover:text-blue-900">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Kembali ke login
                </a>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl sm:p-8">
                    <div class="mb-7">
                        <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-blue-700">
                            <i class="fa-solid fa-key text-2xl"></i>
                        </div>
                        <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">Reset Password</p>
                        <h1 class="mt-2 text-2xl font-black text-slate-900">Lupa password?</h1>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Masukkan email akun SIAKAD. Sistem akan mengirim tautan konfirmasi untuk membuat password baru.
                        </p>
                    </div>

                    <?php if ($pesan_status !== ''): ?>
                        <div class="mb-4 rounded-xl border px-4 py-3 text-sm font-semibold <?= $tipe_status === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
                            <i class="fa-solid <?= $tipe_status === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> mr-2"></i>
                            <?= htmlspecialchars($pesan_status); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Email terdaftar</label>
                            <div class="relative">
                                <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="email" name="email" required autocomplete="email"
                                    class="w-full rounded-xl border border-slate-300 py-3 pl-11 pr-4 focus:ring-2 focus:ring-blue-600 outline-none"
                                    placeholder="nama@email.com">
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full rounded-xl bg-blue-700 px-5 py-3 font-bold text-white shadow-lg shadow-blue-700/20 transition hover:bg-blue-800">
                            <i class="fa-solid fa-paper-plane mr-2"></i>
                            Kirim Link Reset
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <section class="relative hidden overflow-hidden bg-blue-950 lg:flex">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_25%_20%,rgba(34,211,238,0.22),transparent_30%),radial-gradient(circle_at_80%_80%,rgba(16,185,129,0.20),transparent_28%),linear-gradient(135deg,#0f766e_0%,#075985_45%,#172554_100%)]"></div>
            <div class="relative z-10 flex min-h-screen w-full flex-col justify-between p-10 xl:p-12 text-white">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 rounded-2xl bg-white p-2 shadow-lg">
                        <img src="/siakad-atitb/assets/img/logo-atitb.png" alt="Logo ATITB" class="h-full w-full object-contain">
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-cyan-100">SIAKAD ATITB</p>
                        <h2 class="text-2xl font-black tracking-tight">Keamanan Akun</h2>
                    </div>
                </div>

                <div class="max-w-2xl">
                    <div class="mb-6 inline-flex items-center rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold backdrop-blur">
                        <i class="fa-solid fa-shield-halved mr-2 text-emerald-300"></i>
                        Verifikasi email untuk reset password
                    </div>
                    <h3 class="text-4xl font-black leading-tight xl:text-5xl">
                        Link reset hanya berlaku sementara dan dikirim ke email terdaftar.
                    </h3>
                    <p class="mt-5 max-w-xl text-base leading-7 text-blue-50">
                        Demi keamanan, sistem tidak menampilkan apakah email ditemukan atau tidak.
                    </p>
                </div>

                <div class="rounded-2xl border border-white/20 bg-white/10 p-5 backdrop-blur">
                    <p class="text-sm font-bold">Tips keamanan</p>
                    <p class="mt-2 text-sm leading-6 text-blue-50">
                        Gunakan password unik minimal 8 karakter dan jangan membagikan tautan reset kepada siapa pun.
                    </p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
