<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../includes/upload.php";

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$page_title = "Edit Profil";
$page_subtitle = "Perbarui identitas akun dan keamanan login";

$id_user = intval($_SESSION['id_user'] ?? 0);

$query = mysqli_query($conn, "
    SELECT users.*, roles.nama_role
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
    $foto_sql = "";

    if ($username === '' || $nama_lengkap === '') {
        set_alert("error", "Username dan nama lengkap wajib diisi.");
    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,100}$/', $username)) {
        set_alert("error", "Username minimal 3 karakter dan hanya boleh berisi huruf, angka, titik, underscore, atau strip.");
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_alert("error", "Format email tidak valid.");
    } else {
        $username_sql = mysqli_real_escape_string($conn, $username);
        $email_sql = mysqli_real_escape_string($conn, $email);

        $cek_username = mysqli_query($conn, "
            SELECT id_user FROM users
            WHERE username = '$username_sql'
            AND id_user != '$id_user'
            LIMIT 1
        ");

        $cek_email = null;
        if ($email !== '') {
            $cek_email = mysqli_query($conn, "
                SELECT id_user FROM users
                WHERE email = '$email_sql'
                AND id_user != '$id_user'
                LIMIT 1
            ");
        }

        if ($cek_username && mysqli_num_rows($cek_username) > 0) {
            set_alert("error", "Username sudah digunakan pengguna lain.");
        } elseif ($cek_email && mysqli_num_rows($cek_email) > 0) {
            set_alert("error", "Email sudah digunakan pengguna lain.");
        } elseif (($password_lama !== '' || $password_baru !== '' || $konfirmasi_password !== '') && !password_verify($password_lama, $data['password'])) {
            set_alert("error", "Password lama tidak sesuai.");
        } elseif ($password_baru !== '' && strlen($password_baru) < 8) {
            set_alert("error", "Password baru minimal 8 karakter.");
        } elseif ($password_baru !== '' && $password_baru !== $konfirmasi_password) {
            set_alert("error", "Konfirmasi password baru tidak sesuai.");
        } else {
            if (!empty($_FILES['foto']['name'])) {
                $upload = upload_file(
                    $_FILES['foto'],
                    "../../uploads/pengguna",
                    ['jpg', 'jpeg', 'png'],
                    2097152
                );

                if (!$upload['status']) {
                    set_alert("error", $upload['message']);
                    header("Location: edit_profil.php");
                    exit;
                }

                if (!empty($data['foto']) && file_exists("../../uploads/pengguna/" . $data['foto'])) {
                    unlink("../../uploads/pengguna/" . $data['foto']);
                }

                $foto_baru = mysqli_real_escape_string($conn, $upload['filename']);
                $foto_sql = ", foto = '$foto_baru'";
            }

            $nama_lengkap_sql = mysqli_real_escape_string($conn, $nama_lengkap);
            $no_hp_sql = mysqli_real_escape_string($conn, $no_hp);
            $email_update = $email !== '' ? "'$email_sql'" : "NULL";
            $password_sql = "";

            if ($password_baru !== '') {
                $password_hash = mysqli_real_escape_string($conn, password_hash($password_baru, PASSWORD_DEFAULT));
                $password_sql = ", password = '$password_hash'";
            }

            $update = mysqli_query($conn, "
                UPDATE users SET
                    username = '$username_sql',
                    nama_lengkap = '$nama_lengkap_sql',
                    email = $email_update,
                    no_hp = '$no_hp_sql'
                    $password_sql
                    $foto_sql
                WHERE id_user = '$id_user'
            ");

            if ($update) {
                $_SESSION['username'] = $username;
                $_SESSION['nama_lengkap'] = $nama_lengkap;

                simpan_log($conn, $id_user, "Memperbarui profil sendiri", "Profil");

                set_alert("success", "Profil berhasil diperbarui.");
                header("Location: profil.php");
                exit;
            }

            set_alert("error", "Profil gagal diperbarui: " . mysqli_error($conn));
        }
    }
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">
    <?php show_alert(); ?>

    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <section class="xl:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-slate-800">Informasi Profil</h2>
                <p class="text-sm text-slate-500 mt-1">Data ini digunakan untuk identitas akun di seluruh sistem.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" required value="<?= htmlspecialchars($data['nama_lengkap']); ?>"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Username</label>
                    <input type="text" name="username" required value="<?= htmlspecialchars($data['username']); ?>"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    <p class="text-xs text-slate-500 mt-2">Gunakan huruf, angka, titik, underscore, atau strip.</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($data['email'] ?? ''); ?>"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">No. HP / WhatsApp</label>
                    <input type="text" name="no_hp" value="<?= htmlspecialchars($data['no_hp'] ?? ''); ?>"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Role</label>
                    <input type="text" value="<?= htmlspecialchars(str_replace('_', ' ', $data['nama_role'] ?? '-')); ?>" readonly
                        class="w-full rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-slate-600 outline-none capitalize">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Status Akun</label>
                    <input type="text" value="<?= htmlspecialchars($data['status']); ?>" readonly
                        class="w-full rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-slate-600 outline-none capitalize">
                </div>
            </div>

            <div class="mt-8 border-t border-slate-100 pt-6">
                <h2 class="text-xl font-bold text-slate-800">Ubah Password</h2>
                <p class="text-sm text-slate-500 mt-1">Kosongkan bagian ini jika password tidak ingin diganti.</p>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Password Lama</label>
                        <input type="password" name="password_lama" autocomplete="current-password"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Password Baru</label>
                        <input type="password" name="password_baru" autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Konfirmasi Password</label>
                        <input type="password" name="konfirmasi_password" autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    </div>
                </div>
            </div>
        </section>

        <aside class="space-y-6">
            <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Foto Profil</h3>

                <div class="flex items-center gap-4 mb-4">
                    <?php if (!empty($data['foto']) && file_exists("../../uploads/pengguna/" . $data['foto'])): ?>
                        <img src="../../uploads/pengguna/<?= htmlspecialchars($data['foto']); ?>" alt="Foto Profil"
                            class="w-20 h-20 rounded-2xl object-cover border border-slate-200">
                    <?php else: ?>
                        <div class="w-20 h-20 rounded-2xl bg-blue-700 text-white flex items-center justify-center text-3xl font-bold">
                            <?= strtoupper(substr($data['nama_lengkap'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <p class="font-semibold text-slate-800">Foto saat ini</p>
                        <p class="text-xs text-slate-500 mt-1">JPG, JPEG, PNG. Maksimal 2MB.</p>
                    </div>
                </div>

                <input type="file" name="foto" accept="image/png, image/jpeg, image/jpg"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </section>

            <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Simpan Perubahan</h3>

                <div class="space-y-3">
                    <button type="submit"
                        class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-save mr-2"></i>
                        Simpan Profil
                    </button>

                    <a href="profil.php"
                        class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Kembali
                    </a>
                </div>
            </section>
        </aside>
    </form>
</main>

<?php require_once "../../includes/footer.php"; ?>
