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

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Edit Pengguna";
$page_subtitle = "Mengubah data akun pengguna";

$id_user = intval($_GET['id'] ?? 0);

if ($id_user <= 0) {
    set_alert("error", "ID pengguna tidak valid.");
    header("Location: data_pengguna.php");
    exit;
}

$query = mysqli_query($conn, "SELECT * FROM users WHERE id_user='$id_user' LIMIT 1");
if (!$query || mysqli_num_rows($query) < 1) {
    set_alert("error", "Data pengguna tidak ditemukan.");
    header("Location: data_pengguna.php");
    exit;
}

$data = mysqli_fetch_assoc($query);
$roles = mysqli_query($conn, "SELECT * FROM roles ORDER BY nama_role ASC");
if (!$roles) {
    set_alert("error", "Data role gagal dimuat.");
    header("Location: data_pengguna.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_role = intval($_POST['id_role'] ?? 0);
    $username = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap'] ?? ''));
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $no_hp = mysqli_real_escape_string($conn, trim($_POST['no_hp'] ?? ''));
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'aktif');
    $foto_sql = "";

    if (!empty($_FILES['foto']['name'])) {
        $upload = upload_file(
            $_FILES['foto'],
            "../../uploads/pengguna",
            ['jpg', 'jpeg', 'png'],
            2097152
        );

        if (!$upload['status']) {
            set_alert("error", $upload['message']);
        } else {
            if (!empty($data['foto']) && file_exists("../../uploads/pengguna/" . $data['foto'])) {
                unlink("../../uploads/pengguna/" . $data['foto']);
            }

            $foto_baru = mysqli_real_escape_string($conn, $upload['filename']);
            $foto_sql = ", foto='$foto_baru'";
        }
    }

    if ($id_role <= 0 || empty($username) || empty($nama_lengkap)) {
        set_alert("error", "Role, username, dan nama lengkap wajib diisi.");
    } else {
        $cek = mysqli_query($conn, "
            SELECT id_user FROM users 
            WHERE username='$username' AND id_user != '$id_user'
            LIMIT 1
        ");

        if (!$cek) {
            set_alert("error", "Validasi username gagal diproses.");
        } elseif (mysqli_num_rows($cek) > 0) {
            set_alert("error", "Username sudah digunakan pengguna lain.");
        } else {
            $password_sql = "";
            $password_info = "Tidak diubah oleh administrator.";
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $password_sql = ", password='$password_hash'";
                $password_info = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
            }

            $update = mysqli_query($conn, "
                UPDATE users SET
                    id_role='$id_role',
                    username='$username',
                    nama_lengkap='$nama_lengkap',
                    email='$email',
                    no_hp='$no_hp',
                    status='$status'
                    $password_sql
                    $foto_sql
                WHERE id_user='$id_user'
            ");

            if ($update) {
                simpan_log(
                    $conn,
                    $_SESSION['id_user'],
                    "Mengubah data pengguna: $nama_lengkap",
                    "Pengguna"
                );

                kirim_notifikasi(
                    $conn,
                    $id_user,
                    "Data Akun Diperbarui",
                    "Data akun SIAKAD Anda telah diperbarui oleh administrator.",
                    "info",
                    "../dashboard.php"
                );

                $link_login = "http://localhost/siakad-atitb/auth/login.php";
                $gateway_errors = [];

                if (!empty($email)) {
                    // Definisi pesan HTML dengan tabel
                    $pesan_email = "
                        <p>Yth. <strong>$nama_lengkap</strong>,</p>
                        <p>Akun SIAKAD Anda telah berhasil di-update. Berikut adalah detail akun Anda:</p>
        
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
                                <td style='padding: 8px; border: 1px solid #ddd;'>$password_info</td>
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

                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $gateway_errors[] = "email tidak dikirim karena alamat tidak valid";
                    } else {
                        $email_terkirim = kirim_email(
                            $conn,
                            $id_user,
                            $email,
                            "Perubahan Akun SIAKAD ATITB",
                            $pesan_email
                        );

                        if (!$email_terkirim) {
                            $detail_error = email_gateway_last_error();
                            $gateway_errors[] = "email gagal" . ($detail_error ? " ($detail_error)" : "");
                        }
                    }
                }

                if (!empty($no_hp)) {
                    $nomor_whatsapp = normalisasi_nomor_whatsapp($no_hp);

                    // Membuat variabel pesan agar lebih rapi dan mudah dibaca
                    $pesan = "*PERUBAHAN AKUN*\n\n" .
                        "Halo $nama_lengkap, akun SIAKAD ATITB Anda telah berhasil di-update.\n\n" .
                        "Detail Akun:\n" .
                        "- Nama: $nama_lengkap\n" .
                        "- Email: $email\n" .
                        "- No. HP: $no_hp\n" .
                        "- Status: $status\n" .
                        "- Username: $username\n" .
                        "- Password: " . strip_tags($password_info) . "\n\n" .
                        "Silakan login melalui $link_login dan segera ubah password Anda demi keamanan.";

                    if (empty($nomor_whatsapp) || strlen($nomor_whatsapp) < 10) {
                        $gateway_errors[] = "WhatsApp tidak dikirim karena nomor tidak valid";
                    } else {
                        $whatsapp_terkirim = kirim_whatsapp(
                            $conn,
                            $id_user,
                            $nomor_whatsapp,
                            $pesan
                        );

                        if (!$whatsapp_terkirim) {
                            $detail_error = whatsapp_gateway_last_error();
                            $gateway_errors[] = "WhatsApp gagal" . ($detail_error ? " ($detail_error)" : "");
                        }
                    }
                }

                if (!empty($gateway_errors)) {
                    set_alert("warning", "Pengguna berhasil diperbarui, tetapi " . implode("; ", $gateway_errors) . ".");
                } else {
                    set_alert("success", "Pengguna berhasil diperbarui. Notifikasi internal, email, dan WhatsApp telah diproses.");
                }
                header("Location: data_pengguna.php");
                exit;
            }
        }
    }
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">
    <?php show_alert(); ?>

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Edit Pengguna</h2>
            <p class="text-sm text-slate-500">Perbarui data akun pengguna.</p>
        </div>

        <a href="data_pengguna.php"
            class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
        </a>
    </div> -->

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Role</label>
                <select name="id_role" required
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    <?php while ($role = mysqli_fetch_assoc($roles)): ?>
                        <option value="<?= $role['id_role']; ?>" <?= $data['id_role'] == $role['id_role'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars(str_replace('_', ' ', $role['nama_role'])); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" required value="<?= htmlspecialchars($data['nama_lengkap']); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Username</label>
                <input type="text" name="username" required value="<?= htmlspecialchars($data['username']); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Password Baru</label>
                <input type="text" name="password" placeholder="Kosongkan jika tidak diubah"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($data['email'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">No HP / WhatsApp</label>
                <input type="text" name="no_hp" value="<?= htmlspecialchars($data['no_hp'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                <select name="status"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    <option value="aktif" <?= $data['status'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="nonaktif" <?= $data['status'] == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    Foto Pengguna
                </label>

                <div class="flex items-center gap-4 mb-3">
                    <?php if (!empty($data['foto'])): ?>
                        <img src="../../uploads/pengguna/<?= htmlspecialchars($data['foto']); ?>" alt="Foto Pengguna"
                            class="w-16 h-16 rounded-xl object-cover border border-slate-200">
                    <?php else: ?>
                        <div
                            class="w-16 h-16 rounded-xl bg-blue-700 text-white flex items-center justify-center text-xl font-bold">
                            <?= strtoupper(substr($data['nama_lengkap'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <p class="text-sm font-semibold text-slate-700">Foto saat ini</p>
                        <p class="text-xs text-slate-500">Kosongkan jika tidak ingin mengganti foto.</p>
                    </div>
                </div>

                <input type="file" name="foto" accept="image/png, image/jpeg, image/jpg"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

                <p class="text-xs text-slate-500 mt-2">
                    Format: JPG, JPEG, PNG. Maksimal 2MB.
                </p>
            </div>

            <div class="lg:col-span-2 flex flex-col sm:flex-row gap-3 pt-4">
                <button type="submit"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                    <i class="fa-solid fa-save mr-2"></i> Update
                </button>

                <a href="data_pengguna.php"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
                </a>
            </div>

        </form>
    </section>
</main>

<?php require_once "../../includes/footer.php"; ?>
