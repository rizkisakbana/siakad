<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/upload.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/../../includes/notification.php";
require_once __DIR__ . "/../../includes/email_gateway.php";
require_once __DIR__ . "/../../includes/whatsapp_gateway.php";
require_once __DIR__ . "/dosen_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Tambah Dosen";
$page_subtitle = "Menambahkan data dosen dan akun pengguna SIAKAD";

$data_prodi = dosen_fetch_all($conn, "
    SELECT * FROM prodi
    WHERE status = 'aktif'
    ORDER BY nama_prodi ASC
");

$role_dosen = dosen_query_one($conn, "
    SELECT id_role FROM roles 
    WHERE nama_role = 'dosen'
    LIMIT 1
");

$id_role_dosen = $role_dosen['id_role'] ?? 0;
$link_login = "http://localhost/siakad-atitb/auth/login.php";
$ref_agama = dosen_ref_options($conn, 'agama');
$ref_ikatan = dosen_ref_options($conn, 'ikatan_kerja_sdm');
$ref_status_aktif = dosen_ref_options($conn, 'status_keaktifan_pegawai');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_prodi = intval($_POST['id_prodi'] ?? 0);
    $nidn = mysqli_real_escape_string($conn, trim($_POST['nidn'] ?? ''));
    $nidk = mysqli_real_escape_string($conn, trim($_POST['nidk'] ?? ''));
    $nuptk = mysqli_real_escape_string($conn, trim($_POST['nuptk'] ?? ''));
    $nip = mysqli_real_escape_string($conn, trim($_POST['nip'] ?? ''));
    $id_feeder = mysqli_real_escape_string($conn, trim($_POST['id_dosen_feeder'] ?? ''));
    $id_dosen_feeder = $id_feeder;
    $nama_dosen = mysqli_real_escape_string($conn, trim($_POST['nama_dosen'] ?? ''));
    $gelar_depan = mysqli_real_escape_string($conn, trim($_POST['gelar_depan'] ?? ''));
    $gelar_belakang = mysqli_real_escape_string($conn, trim($_POST['gelar_belakang'] ?? ''));
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin'] ?? '');
    $tempat_lahir = mysqli_real_escape_string($conn, trim($_POST['tempat_lahir'] ?? ''));
    $tanggal_lahir = mysqli_real_escape_string($conn, $_POST['tanggal_lahir'] ?? '');
    $id_agama_feeder = mysqli_real_escape_string($conn, trim($_POST['id_agama_feeder'] ?? ''));
    $agama = mysqli_real_escape_string($conn, dosen_ref_name($ref_agama, $id_agama_feeder));
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $no_hp = mysqli_real_escape_string($conn, trim($_POST['no_hp'] ?? ''));
    $alamat = mysqli_real_escape_string($conn, trim($_POST['alamat'] ?? ''));
    $status_dosen = mysqli_real_escape_string($conn, $_POST['status_dosen'] ?? 'tetap');
    $id_status_aktif_feeder = mysqli_real_escape_string($conn, trim($_POST['id_status_aktif_feeder'] ?? ''));
    $nama_status_aktif = mysqli_real_escape_string($conn, dosen_ref_name($ref_status_aktif, $id_status_aktif_feeder));
    $id_ikatan_kerja_feeder = mysqli_real_escape_string($conn, trim($_POST['id_ikatan_kerja_feeder'] ?? ''));
    $nama_ikatan_kerja = mysqli_real_escape_string($conn, dosen_ref_name($ref_ikatan, $id_ikatan_kerja_feeder));
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'aktif');
    $id_prodi_feeder = mysqli_real_escape_string($conn, dosen_prodi_feeder($conn, $id_prodi));

    $username = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($id_prodi <= 0 || empty($nama_dosen) || empty($username) || empty($password)) {
        set_alert("error", "Program studi, nama dosen, username, dan password wajib diisi.");
    } elseif ($id_role_dosen <= 0) {
        set_alert("error", "Role dosen belum tersedia pada tabel roles.");
    } else {
        $cek_user_exists = dosen_query_exists($conn, "
            SELECT id_user FROM users 
            WHERE username = '$username'
            LIMIT 1
        ");

        $cek_email_user_exists = false;
        if (!empty($email)) {
            $cek_email_user_exists = dosen_query_exists($conn, "
                SELECT id_user FROM users
                WHERE email = '$email'
                LIMIT 1
            ");
        }

        $cek_nidn_exists = false;

        if (!empty($nidn)) {
            $cek_nidn_exists = dosen_query_exists($conn, "
                SELECT id_dosen FROM dosen 
                WHERE nidn = '$nidn'
                LIMIT 1
            ");
        }

        $cek_feeder_exists = false;
        if (!empty($id_dosen_feeder)) {
            $cek_feeder_exists = dosen_query_exists($conn, "
                SELECT id_dosen FROM dosen
                WHERE id_dosen_feeder = '$id_dosen_feeder' OR id_feeder = '$id_dosen_feeder'
                LIMIT 1
            ");
        }

        if ($cek_user_exists === null) {
            set_alert("error", "Validasi username gagal diproses.");
        } elseif ($cek_email_user_exists === null) {
            set_alert("error", "Validasi email gagal diproses.");
        } elseif ($cek_nidn_exists === null) {
            set_alert("error", "Validasi NIDN gagal diproses.");
        } elseif ($cek_feeder_exists === null) {
            set_alert("error", "Validasi ID dosen NeoFeeder gagal diproses.");
        } elseif ($cek_user_exists) {
            set_alert("error", "Username sudah digunakan.");
        } elseif ($cek_email_user_exists) {
            set_alert("error", "Email sudah digunakan akun pengguna lain.");
        } elseif ($cek_nidn_exists) {
            set_alert("error", "NIDN sudah digunakan.");
        } elseif ($cek_feeder_exists) {
            set_alert("error", "ID dosen NeoFeeder sudah digunakan.");
        } else {
            $foto = null;

            if (!empty($_FILES['foto']['name'])) {
                $upload = upload_file(
                    $_FILES['foto'],
                    "../../uploads/dosen",
                    ['jpg', 'jpeg', 'png'],
                    2097152
                );

                if (!$upload['status']) {
                    set_alert("error", $upload['message']);
                } else {
                    $foto = $upload['filename'];
                }
            }

            if (!isset($_SESSION['alert'])) {
                mysqli_begin_transaction($conn);

                try {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $email_sql = dosen_db_value($conn, $email);
                    $foto_sql_user = dosen_db_value($conn, $foto);
                    $status_sync_feeder = !empty($id_dosen_feeder) ? 'sudah' : 'belum';

                    $simpan_user = mysqli_query($conn, "
                        INSERT INTO users
                        (id_role, username, password, nama_lengkap, email, no_hp, foto, status)
                        VALUES
                        ('$id_role_dosen', '$username', '$password_hash', '$nama_dosen', $email_sql, '$no_hp', $foto_sql_user, '$status')
                    ");

                    if (!$simpan_user) {
                        throw new Exception("Gagal menyimpan akun pengguna.");
                    }

                    $id_user_baru = mysqli_insert_id($conn);

                    $simpan_dosen = mysqli_query($conn, "
                        INSERT INTO dosen
                        (
                            id_user,
                            id_prodi,
                            id_prodi_feeder,
                            id_feeder,
                            id_dosen_feeder,
                            nidn,
                            nidk,
                            nuptk,
                            nip,
                            nama_dosen,
                            gelar_depan,
                            gelar_belakang,
                            jenis_kelamin,
                            tempat_lahir,
                            tanggal_lahir,
                            id_agama_feeder,
                            agama,
                            email,
                            no_hp,
                            foto,
                            alamat,
                            status_dosen,
                            id_status_aktif_feeder,
                            nama_status_aktif,
                            id_ikatan_kerja_feeder,
                            nama_ikatan_kerja,
                            status_sync_feeder,
                            last_sync_feeder,
                            status
                        )
                        VALUES
                        (
                            '$id_user_baru',
                            '$id_prodi',
                            " . dosen_db_value($conn, $id_prodi_feeder) . ",
                            " . dosen_db_value($conn, $id_feeder) . ",
                            " . dosen_db_value($conn, $id_dosen_feeder) . ",
                            '$nidn',
                            " . dosen_db_value($conn, $nidk) . ",
                            " . dosen_db_value($conn, $nuptk) . ",
                            '$nip',
                            '$nama_dosen',
                            '$gelar_depan',
                            '$gelar_belakang',
                            '$jenis_kelamin',
                            '$tempat_lahir',
                            " . (!empty($tanggal_lahir) ? "'$tanggal_lahir'" : "NULL") . ",
                            " . dosen_db_value($conn, $id_agama_feeder) . ",
                            " . dosen_db_value($conn, $agama) . ",
                            " . dosen_db_value($conn, $email) . ",
                            '$no_hp',
                            '$foto',
                            '$alamat',
                            '$status_dosen',
                            " . dosen_db_value($conn, $id_status_aktif_feeder) . ",
                            " . dosen_db_value($conn, $nama_status_aktif) . ",
                            " . dosen_db_value($conn, $id_ikatan_kerja_feeder) . ",
                            " . dosen_db_value($conn, $nama_ikatan_kerja) . ",
                            '$status_sync_feeder',
                            " . (!empty($id_dosen_feeder) ? "NOW()" : "NULL") . ",
                            '$status'
                        )
                    ");

                    if (!$simpan_dosen) {
                        throw new Exception("Gagal menyimpan data dosen.");
                    }

                    mysqli_commit($conn);

                    simpan_log(
                        $conn,
                        $_SESSION['id_user'],
                        "Menambahkan data dosen: $nama_dosen",
                        "Dosen"
                    );

                    kirim_notifikasi(
                        $conn,
                        $id_user_baru,
                        "Akun Dosen SIAKAD Dibuat",
                        "Akun dosen Anda telah berhasil dibuat. Silakan login menggunakan username yang telah diberikan.",
                        "success",
                        "../dosen/dashboard.php"
                    );

                    if (!empty($email)) {
                        $pesan_email = "
                            <p>Yth. <strong>$nama_dosen</strong>,</p>
                            <p>Akun Dosen SIAKAD Anda telah berhasil dibuat. Berikut adalah detail akun Anda:</p>

                            <table style='border-collapse: collapse; width: 100%; max-width: 500px; border: 1px solid #ddd;'>
                                <tr>
                                    <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Nama Dosen</td>
                                    <td style='padding: 8px; border: 1px solid #ddd;'>$gelar_depan $nama_dosen $gelar_belakang</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>NIDN</td>
                                    <td style='padding: 8px; border: 1px solid #ddd;'>$nidn</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>NIP</td>
                                    <td style='padding: 8px; border: 1px solid #ddd;'>$nip</td>
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
                                    <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Status Dosen</td>
                                    <td style='padding: 8px; border: 1px solid #ddd;'>$status_dosen</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Status Akun</td>
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

                            <p>Silakan login melalui link berikut:</p>

                            <p>
                                <a href='$link_login' style='background:#2563eb;color:white;padding:12px 18px;text-decoration:none;border-radius:8px;display:inline-block;'>
                                    Login SIAKAD
                                </a>
                            </p>

                            <p>Demi keamanan, segera ubah password setelah berhasil login.</p>
                            <p>Terima kasih.</p>
                        ";

                        kirim_email(
                            $conn,
                            $id_user_baru,
                            $email,
                            "Informasi Akun Dosen SIAKAD ATITB",
                            $pesan_email
                        );
                    }

                    if (!empty($no_hp)) {
                        $pesan_wa = "*AKUN DOSEN SIAKAD ATITB*\n\n" .
                            "Halo $nama_dosen, akun Dosen SIAKAD ATITB Anda telah berhasil dibuat.\n\n" .
                            "Detail Akun:\n" .
                            "- Nama: $gelar_depan $nama_dosen $gelar_belakang\n" .
                            "- NIDN: $nidn\n" .
                            "- NIP: $nip\n" .
                            "- Email: $email\n" .
                            "- No. HP: $no_hp\n" .
                            "- Status Dosen: $status_dosen\n" .
                            "- Status Akun: $status\n" .
                            "- Username: $username\n" .
                            "- Password: $password\n\n" .
                            "Silakan login melalui $link_login dan segera ubah password Anda demi keamanan.";

                        kirim_whatsapp(
                            $conn,
                            $id_user_baru,
                            $no_hp,
                            $pesan_wa
                        );
                    }

                    set_alert("success", "Data dosen berhasil ditambahkan.");
                    header("Location: data_dosen.php");
                    exit;

                } catch (Exception $e) {
                    mysqli_rollback($conn);

                    if (!empty($foto) && file_exists("../../uploads/dosen/" . $foto)) {
                        unlink("../../uploads/dosen/" . $foto);
                    }

                    set_alert("error", $e->getMessage());
                }
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
            <h2 class="text-xl font-bold text-slate-800">Tambah Dosen</h2>
            <p class="text-sm text-slate-500">
                Lengkapi data dosen dan akun login dosen.
            </p>
        </div>

        <a href="data_dosen.php"
           class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div> -->

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Program Studi</label>
                    <select name="id_prodi" required class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="">-- Pilih Program Studi --</option>
                        <?php foreach ($data_prodi as $prodi): ?>
                            <option value="<?= $prodi['id_prodi']; ?>">
                                <?= htmlspecialchars($prodi['nama_prodi']); ?> - <?= htmlspecialchars($prodi['jenjang']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">NIDN</label>
                    <input type="text" name="nidn" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">NIDK</label>
                    <input type="text" name="nidk" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">NUPTK</label>
                    <input type="text" name="nuptk" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">NIP</label>
                    <input type="text" name="nip" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">ID Dosen NeoFeeder</label>
                    <input type="text" name="id_dosen_feeder" placeholder="Kosongkan jika belum ada"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Gelar Depan</label>
                    <input type="text" name="gelar_depan" placeholder="Dr., Prof., Ir." class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Gelar Belakang</label>
                    <input type="text" name="gelar_belakang" placeholder="S.Kom., M.Kom." class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Dosen</label>
                    <input type="text" name="nama_dosen" required class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Agama PDDikti</label>
                    <?php dosen_render_ref_select('id_agama_feeder', $ref_agama, '', '-- Pilih Agama --'); ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Status Dosen</label>
                    <select name="status_dosen" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="tetap">Tetap</option>
                        <option value="tidak tetap">Tidak Tetap</option>
                        <option value="honorer">Honorer</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Status Aktif PDDikti</label>
                    <?php dosen_render_ref_select('id_status_aktif_feeder', $ref_status_aktif, '', '-- Pilih Status Aktif --'); ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Ikatan Kerja PDDikti</label>
                    <?php dosen_render_ref_select('id_ikatan_kerja_feeder', $ref_ikatan, '', '-- Pilih Ikatan Kerja --'); ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                    <input type="email" name="email" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">No. HP / WhatsApp</label>
                    <input type="text" name="no_hp" placeholder="628xxxxxxxxxx" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat</label>
                    <textarea name="alamat" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Username</label>
                    <input type="text" name="username" required class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Password Awal</label>
                    <input type="text" name="password" required class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Foto Dosen</label>
                    <input type="file" name="foto" accept="image/png, image/jpeg, image/jpg"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    <p class="text-xs text-slate-500 mt-2">Format JPG, JPEG, PNG. Maksimal 2MB.</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Status Akun</label>
                    <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div class="lg:col-span-2 flex flex-col sm:flex-row gap-3 pt-4">
                    <button type="submit"
                            class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-save mr-2"></i>
                        Simpan
                    </button>

                    <a href="data_dosen.php"
                       class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Kembali
                    </a>
                </div>

            </form>

        </div>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Informasi Pengisian</h3>

            <div class="space-y-4 text-sm text-slate-600">
                <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                    <p class="font-semibold text-blue-700 mb-1">Akun Dosen</p>
                    <p>Sistem otomatis membuat akun login dosen sesuai username dan password.</p>
                </div>

                <div class="p-4 rounded-xl bg-green-50 border border-green-100">
                    <p class="font-semibold text-green-700 mb-1">Notifikasi Gateway</p>
                    <p>Email dan WhatsApp dikirim jika email dan nomor HP tersedia.</p>
                </div>

                <div class="p-4 rounded-xl bg-orange-50 border border-orange-100">
                    <p class="font-semibold text-orange-700 mb-1">Relasi Prodi</p>
                    <p>Dosen wajib terhubung ke program studi untuk kebutuhan jadwal, kelas, dan laporan.</p>
                </div>
            </div>
        </aside>

    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
