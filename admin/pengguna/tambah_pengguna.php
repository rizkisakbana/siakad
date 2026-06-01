<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/../../includes/notification.php";
require_once __DIR__ . "/../../includes/email_gateway.php";
require_once __DIR__ . "/../../includes/whatsapp_gateway.php";
require_once __DIR__ . "/../../includes/upload.php";
require_once __DIR__ . "/pengguna_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Tambah Pengguna";
$page_subtitle = "Menambahkan akun pengguna sistem";

$roles = pengguna_all($conn, "SELECT * FROM roles ORDER BY nama_role ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_role = intval($_POST['id_role'] ?? 0);
    $username = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap'] ?? ''));
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $no_hp = mysqli_real_escape_string($conn, trim($_POST['no_hp'] ?? ''));
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'aktif');
    $foto = null;

    if (!empty($_FILES['foto']['name'])) {
        $upload = upload_file(
            $_FILES['foto'],
            __DIR__ . "/../../uploads/pengguna",
            ['jpg', 'jpeg', 'png'],
            2097152
        );

        if (!$upload['status']) {
            set_alert("error", $upload['message']);
        } else {
            $foto = $upload['filename'];
        }
    }

    if ($id_role <= 0 || empty($username) || empty($password) || empty($nama_lengkap)) {
        set_alert("error", "Role, username, password, dan nama lengkap wajib diisi.");
    } else {
        $cek = pengguna_one($conn, "SELECT id_user FROM users WHERE username='$username' LIMIT 1");
        $cek_email = !empty($email)
            ? pengguna_one($conn, "SELECT id_user FROM users WHERE email='$email' LIMIT 1")
            : null;

        if ($cek) {
            set_alert("error", "Username sudah digunakan.");
        } elseif ($cek_email) {
            set_alert("error", "Email sudah digunakan pengguna lain.");
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $email_sql = pengguna_sql_value($conn, $email);
            $no_hp_sql = pengguna_sql_value($conn, $no_hp);
            $foto_sql = pengguna_sql_value($conn, $foto);

            $simpan = pengguna_execute($conn, "
                INSERT INTO users 
                (id_role, username, password, nama_lengkap, email, no_hp, foto, status)
                VALUES 
                ('$id_role', '$username', '$password_hash', '$nama_lengkap', $email_sql, $no_hp_sql, $foto_sql, '$status')
            ");

            if ($simpan) {
                $id_user_baru = mysqli_insert_id($conn);

                simpan_log($conn, $_SESSION['id_user'], "Menambahkan pengguna: $nama_lengkap", "Pengguna");

                kirim_notifikasi(
                    $conn,
                    $id_user_baru,
                    "Akun SIAKAD Dibuat",
                    "Akun Anda telah dibuat. Username: $username",
                    "success",
                    "../dashboard.php"
                );

                $link_login = "http://localhost/siakad-atitb/auth/login.php";

                if (!empty($email)) {
                    // Definisi pesan HTML dengan tabel
                    $pesan_email = "
                        <p>Yth. <strong>$nama_lengkap</strong>,</p>
                        <p>Akun SIAKAD Anda telah berhasil dibuat. Berikut adalah detail akun Anda:</p>
                        
                        <table style='border-collapse: collapse; width: 100%; max-width: 400px; border: 1px solid #ddd;'>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Nama Lengkap</td>
                                <td style='padding: 8px; border: 1px solid #ddd;'>$nama_lengkap</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Email</td>
                                <td style='padding: 8px; border: 1px solid #ddd;'>$email</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>No. HP</td>
                                <td style='padding: 8px; border: 1px solid #ddd;'>$no_hp</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Status</td>
                                <td style='padding: 8px; border: 1px solid #ddd;'>$status</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Username</td>
                                <td style='padding: 8px; border: 1px solid #ddd;'>$username</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Password</td>
                                <td style='padding: 8px; border: 1px solid #ddd;'>$password</td>
                            </tr>
                        </table>
                        
                        <p>
                            Silakan login melalui link berikut:
                        </p>

                        <p>
                            <a href='$link_login' style='background:#2563eb;color:white;padding:12px 18px;text-decoration:none;border-radius:8px;display:inline-block;'>
                                Login SIAKAD
                            </a>
                        </p>

                        <p>
                            Demi keamanan, segera ubah password setelah berhasil login.
                        </p>
                        
                        <p>Terima kasih.</p>
                    ";

                    kirim_email(
                        $conn,
                        $id_user_baru,
                        $email,
                        "Informasi Akun SIAKAD ATITB",
                        $pesan_email
                    );
                }

                if (!empty($no_hp)) {
                    // Membuat variabel pesan agar lebih rapi dan mudah dibaca
                    $pesan = "*AKUN SIAKAD ATITB*\n\n" .
                        "Halo $nama_lengkap, akun SIAKAD ATITB Anda telah berhasil dibuat.\n\n" .
                        "Detail Akun:\n" .
                        "- Nama: $nama_lengkap\n" .
                        "- Email: $email\n" .
                        "- No. HP: $no_hp\n" .
                        "- Status: $status\n" .
                        "- Username: $username\n" .
                        "- Password: $password\n\n" .
                        "Silakan login melalui $link_login dan segera ubah password Anda demi keamanan.";

                    kirim_whatsapp(
                        $conn,
                        $id_user_baru,
                        $no_hp,
                        $pesan
                    );
                }

                set_alert("success", "Pengguna berhasil ditambahkan.");
                header("Location: data_pengguna.php");
                exit;
            } else {
                set_alert("error", "Pengguna gagal ditambahkan. Periksa kembali username, email, dan data wajib lainnya.");
            }
        }
    }
}

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">
    <?php show_alert(); ?>

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Tambah Pengguna</h2>
            <p class="text-sm text-slate-500">Lengkapi form berikut untuk membuat akun pengguna.</p>
        </div>

        <a href="data_pengguna.php" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
        </a>
    </div> -->

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Role</label>
                <select name="id_role" required
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    <option value="">-- Pilih Role --</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id_role']; ?>">
                            <?= htmlspecialchars(str_replace('_', ' ', $role['nama_role'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" required
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Username</label>
                <input type="text" name="username" required
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Password Awal</label>
                <input type="text" name="password" required
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                <input type="email" name="email"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">No HP / WhatsApp</label>
                <input type="text" name="no_hp" placeholder="628xxxxxxxxxx"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    Foto Pengguna
                </label>

                <input type="file" name="foto" accept="image/png, image/jpeg, image/jpg"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

                <p class="text-xs text-slate-500 mt-2">
                    Format: JPG, JPEG, PNG. Maksimal 2MB.
                </p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                <select name="status"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>

            <div class="lg:col-span-2 flex flex-col sm:flex-row gap-3 pt-4">
                <button type="submit"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                    <i class="fa-solid fa-save mr-2"></i> Simpan
                </button>

                <a href="data_pengguna.php"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
                </a>
            </div>

        </form>
    </section>
</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
