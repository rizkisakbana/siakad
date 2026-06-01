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

$page_title = "Edit Dosen";
$page_subtitle = "Mengubah data dosen dan akun pengguna";

$id_dosen = intval($_GET['id'] ?? 0);

if ($id_dosen <= 0) {
    set_alert("error", "ID dosen tidak valid.");
    header("Location: data_dosen.php");
    exit;
}

$data = dosen_query_one($conn, "
    SELECT 
        dosen.*,
        users.username,
        users.status AS status_user,
        prodi.nama_prodi,
        prodi.jenjang
    FROM dosen
    LEFT JOIN users ON dosen.id_user = users.id_user
    LEFT JOIN prodi ON dosen.id_prodi = prodi.id_prodi
    WHERE dosen.id_dosen = '$id_dosen'
    LIMIT 1
");

if (!$data) {
    set_alert("error", "Data dosen tidak ditemukan.");
    header("Location: data_dosen.php");
    exit;
}

$data_prodi = dosen_fetch_all($conn, "
    SELECT * FROM prodi
    WHERE status = 'aktif'
    ORDER BY nama_prodi ASC
");

$link_login = "http://localhost/siakad-atitb/auth/login.php";
$ref_agama = dosen_ref_options($conn, 'agama');
$ref_ikatan = dosen_ref_options($conn, 'ikatan_kerja_sdm');
$ref_status_aktif = dosen_ref_options($conn, 'status_keaktifan_pegawai');
$role_dosen = dosen_query_one($conn, "
    SELECT id_role FROM roles
    WHERE nama_role = 'dosen'
    LIMIT 1
");
$id_role_dosen = $role_dosen['id_role'] ?? 0;

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
    if (empty($id_agama_feeder) && !empty($data['agama'])) {
        $agama = mysqli_real_escape_string($conn, $data['agama']);
    }
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $no_hp = mysqli_real_escape_string($conn, trim($_POST['no_hp'] ?? ''));
    $alamat = mysqli_real_escape_string($conn, trim($_POST['alamat'] ?? ''));
    $status_dosen = mysqli_real_escape_string($conn, $_POST['status_dosen'] ?? 'tetap');
    $id_status_aktif_feeder = mysqli_real_escape_string($conn, trim($_POST['id_status_aktif_feeder'] ?? ''));
    $nama_status_aktif = mysqli_real_escape_string($conn, dosen_ref_name($ref_status_aktif, $id_status_aktif_feeder));
    if (empty($id_status_aktif_feeder) && !empty($data['nama_status_aktif'])) {
        $nama_status_aktif = mysqli_real_escape_string($conn, $data['nama_status_aktif']);
    }
    $id_ikatan_kerja_feeder = mysqli_real_escape_string($conn, trim($_POST['id_ikatan_kerja_feeder'] ?? ''));
    $nama_ikatan_kerja = mysqli_real_escape_string($conn, dosen_ref_name($ref_ikatan, $id_ikatan_kerja_feeder));
    if (empty($id_ikatan_kerja_feeder) && !empty($data['nama_ikatan_kerja'])) {
        $nama_ikatan_kerja = mysqli_real_escape_string($conn, $data['nama_ikatan_kerja']);
    }
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'aktif');
    $id_prodi_feeder = mysqli_real_escape_string($conn, dosen_prodi_feeder($conn, $id_prodi));

    $username = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($id_prodi <= 0 || empty($nama_dosen)) {
        set_alert("error", "Program studi dan nama dosen wajib diisi.");
    } else {
        $id_user = intval($data['id_user']);

        if ($id_user > 0 && empty($username)) {
            set_alert("error", "Username wajib diisi karena dosen sudah memiliki akun pengguna.");
        }
    }

    if (!isset($_SESSION['alert'])) {
        $id_user = intval($data['id_user']);

        $cek_user_exists = false;
        if (!empty($username)) {
            $cek_user_exists = dosen_query_exists($conn, "
                SELECT id_user 
                FROM users 
                WHERE username = '$username'
                AND id_user != '$id_user'
                LIMIT 1
            ");
        }

        $cek_email_user_exists = false;
        if (!empty($email)) {
            $cek_email_user_exists = dosen_query_exists($conn, "
                SELECT id_user
                FROM users
                WHERE email = '$email'
                AND id_user != '$id_user'
                LIMIT 1
            ");
        }

        $cek_nidn_exists = false;

        if (!empty($nidn)) {
            $cek_nidn_exists = dosen_query_exists($conn, "
                SELECT id_dosen 
                FROM dosen 
                WHERE nidn = '$nidn'
                AND id_dosen != '$id_dosen'
                LIMIT 1
            ");
        }

        $cek_feeder_exists = false;
        if (!empty($id_dosen_feeder)) {
            $cek_feeder_exists = dosen_query_exists($conn, "
                SELECT id_dosen
                FROM dosen
                WHERE (id_dosen_feeder = '$id_dosen_feeder' OR id_feeder = '$id_dosen_feeder')
                AND id_dosen != '$id_dosen'
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
            $foto_sql = "";
            $foto_baru = "";

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
                    $foto_baru = mysqli_real_escape_string($conn, $upload['filename']);
                    $foto_sql = ", foto = '$foto_baru'";
                }
            }

            if (!isset($_SESSION['alert'])) {
                mysqli_begin_transaction($conn);

                try {
                    $password_sql = "";

                    if (!empty($password)) {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $password_sql = ", password = '$password_hash'";
                    }

                    if ($id_user > 0) {
                        $update_user = mysqli_query($conn, "
                            UPDATE users SET
                                username = " . dosen_db_value($conn, $username) . ",
                                nama_lengkap = '$nama_dosen',
                                email = " . dosen_db_value($conn, $email) . ",
                                no_hp = '$no_hp',
                                status = '$status'
                                $password_sql
                            WHERE id_user = '$id_user'
                        ");
                    } elseif (!empty($username) || !empty($password)) {
                        if (empty($username) || empty($password)) {
                            throw new Exception("Username dan password wajib diisi jika ingin membuat akun dosen.");
                        }

                        if ($id_role_dosen <= 0) {
                            throw new Exception("Role dosen belum tersedia pada tabel roles.");
                        }

                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $update_user = mysqli_query($conn, "
                            INSERT INTO users
                            (id_role, username, password, nama_lengkap, email, no_hp, foto, status)
                            VALUES
                            ('$id_role_dosen', '$username', '$password_hash', '$nama_dosen', " . dosen_db_value($conn, $email) . ", '$no_hp', " . dosen_db_value($conn, $data['foto'] ?? '') . ", '$status')
                        ");
                        $id_user = mysqli_insert_id($conn);
                    } else {
                        $update_user = true;
                    }

                    if (!$update_user) {
                        throw new Exception("Gagal memperbarui akun pengguna.");
                    }

                    $tanggal_lahir_sql = !empty($tanggal_lahir) ? "'$tanggal_lahir'" : "NULL";

                    $update_dosen = mysqli_query($conn, "
                        UPDATE dosen SET
                            id_user = " . ($id_user > 0 ? "'$id_user'" : "NULL") . ",
                            id_prodi = '$id_prodi',
                            id_prodi_feeder = " . dosen_db_value($conn, $id_prodi_feeder) . ",
                            id_feeder = " . dosen_db_value($conn, $id_feeder) . ",
                            id_dosen_feeder = " . dosen_db_value($conn, $id_dosen_feeder) . ",
                            nidn = '$nidn',
                            nidk = " . dosen_db_value($conn, $nidk) . ",
                            nuptk = " . dosen_db_value($conn, $nuptk) . ",
                            nip = '$nip',
                            nama_dosen = '$nama_dosen',
                            gelar_depan = '$gelar_depan',
                            gelar_belakang = '$gelar_belakang',
                            jenis_kelamin = '$jenis_kelamin',
                            tempat_lahir = '$tempat_lahir',
                            tanggal_lahir = $tanggal_lahir_sql,
                            id_agama_feeder = " . dosen_db_value($conn, $id_agama_feeder) . ",
                            agama = " . dosen_db_value($conn, $agama) . ",
                            email = " . dosen_db_value($conn, $email) . ",
                            no_hp = '$no_hp',
                            alamat = '$alamat',
                            status_dosen = '$status_dosen',
                            id_status_aktif_feeder = " . dosen_db_value($conn, $id_status_aktif_feeder) . ",
                            nama_status_aktif = " . dosen_db_value($conn, $nama_status_aktif) . ",
                            id_ikatan_kerja_feeder = " . dosen_db_value($conn, $id_ikatan_kerja_feeder) . ",
                            nama_ikatan_kerja = " . dosen_db_value($conn, $nama_ikatan_kerja) . ",
                            status_sync_feeder = " . (!empty($id_dosen_feeder) ? "'sudah'" : "'belum'") . ",
                            last_sync_feeder = " . (!empty($id_dosen_feeder) ? "COALESCE(last_sync_feeder, NOW())" : "last_sync_feeder") . ",
                            last_error_feeder = NULL,
                            status = '$status'
                            $foto_sql
                        WHERE id_dosen = '$id_dosen'
                    ");

                    if (!$update_dosen) {
                        throw new Exception("Gagal memperbarui data dosen.");
                    }

                    mysqli_commit($conn);

                    if (!empty($foto_baru) && !empty($data['foto']) && file_exists("../../uploads/dosen/" . $data['foto'])) {
                        unlink("../../uploads/dosen/" . $data['foto']);
                    }

                    simpan_log(
                        $conn,
                        $_SESSION['id_user'],
                        "Mengubah data dosen: $nama_dosen",
                        "Dosen"
                    );

                    if ($id_user > 0) {
                        kirim_notifikasi(
                            $conn,
                            $id_user,
                            "Data Dosen Diperbarui",
                            "Data akun dan profil dosen Anda telah diperbarui oleh administrator.",
                            "info",
                            "../dosen/dashboard.php"
                        );
                    }

                    if (!empty($email)) {
                        $pesan_email = "
                            <p>Yth. <strong>$nama_dosen</strong>,</p>
                            <p>Data akun dan profil Dosen SIAKAD Anda telah diperbarui. Berikut adalah informasi terbaru Anda:</p>

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
                                    <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Jenis Kelamin</td>
                                    <td style='padding: 8px; border: 1px solid #ddd;'>$jenis_kelamin</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Tempat/Tanggal Lahir</td>
                                    <td style='padding: 8px; border: 1px solid #ddd;'>$tempat_lahir / $tanggal_lahir</td>
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
                            </table>

                            <p>Silakan login melalui link berikut:</p>

                            <p>
                                <a href='$link_login' style='background:#2563eb;color:white;padding:12px 18px;text-decoration:none;border-radius:8px;display:inline-block;'>
                                    Login SIAKAD
                                </a>
                            </p>

                            <p>Jika Anda merasa tidak mengetahui perubahan ini, segera hubungi admin akademik.</p>
                            <p>Terima kasih.</p>
                        ";

                        kirim_email(
                            $conn,
                            $id_user,
                            $email,
                            "Informasi Perubahan Data Dosen SIAKAD ATITB",
                            $pesan_email
                        );
                    }

                    if (!empty($no_hp)) {
                        $pesan_wa = "*PERUBAHAN DATA DOSEN SIAKAD ATITB*\n\n" .
                            "Halo $nama_dosen, data akun dan profil Dosen SIAKAD ATITB Anda telah diperbarui.\n\n" .
                            "Informasi Terbaru:\n" .
                            "- Nama: $gelar_depan $nama_dosen $gelar_belakang\n" .
                            "- NIDN: $nidn\n" .
                            "- NIP: $nip\n" .
                            "- Jenis Kelamin: $jenis_kelamin\n" .
                            "- Tempat/Tanggal Lahir: $tempat_lahir / $tanggal_lahir\n" .
                            "- Email: $email\n" .
                            "- No. HP: $no_hp\n" .
                            "- Status Dosen: $status_dosen\n" .
                            "- Status Akun: $status\n" .
                            "- Username: $username\n\n" .
                            "Silakan login melalui $link_login. Jika Anda tidak mengetahui perubahan ini, segera hubungi admin akademik.";

                        kirim_whatsapp(
                            $conn,
                            $id_user,
                            $no_hp,
                            $pesan_wa
                        );
                    }

                    set_alert("success", "Data dosen berhasil diperbarui.");
                    header("Location: data_dosen.php");
                    exit;

                } catch (Exception $e) {
                    mysqli_rollback($conn);

                    if (!empty($foto_baru) && file_exists("../../uploads/dosen/" . $foto_baru)) {
                        unlink("../../uploads/dosen/" . $foto_baru);
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
            <h2 class="text-xl font-bold text-slate-800">Edit Dosen</h2>
            <p class="text-sm text-slate-500">
                Perbarui data lengkap dosen dan akun login dosen.
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
                            <option value="<?= $prodi['id_prodi']; ?>" <?= $data['id_prodi'] == $prodi['id_prodi'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($prodi['nama_prodi']); ?> - <?= htmlspecialchars($prodi['jenjang']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">NIDN</label>
                    <input type="text" name="nidn" value="<?= htmlspecialchars($data['nidn'] ?? ''); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">NIDK</label>
                    <input type="text" name="nidk" value="<?= htmlspecialchars($data['nidk'] ?? ''); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">NUPTK</label>
                    <input type="text" name="nuptk" value="<?= htmlspecialchars($data['nuptk'] ?? ''); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">NIP</label>
                    <input type="text" name="nip" value="<?= htmlspecialchars($data['nip'] ?? ''); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">ID Dosen NeoFeeder</label>
                    <input type="text" name="id_dosen_feeder" value="<?= htmlspecialchars($data['id_dosen_feeder'] ?: ($data['id_feeder'] ?? '')); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Gelar Depan</label>
                    <input type="text" name="gelar_depan" value="<?= htmlspecialchars($data['gelar_depan'] ?? ''); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Gelar Belakang</label>
                    <input type="text" name="gelar_belakang" value="<?= htmlspecialchars($data['gelar_belakang'] ?? ''); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Dosen</label>
                    <input type="text" name="nama_dosen" required value="<?= htmlspecialchars($data['nama_dosen']); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="L" <?= ($data['jenis_kelamin'] ?? '') == 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                        <option value="P" <?= ($data['jenis_kelamin'] ?? '') == 'P' ? 'selected' : ''; ?>>Perempuan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" value="<?= htmlspecialchars($data['tempat_lahir'] ?? ''); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" value="<?= htmlspecialchars($data['tanggal_lahir'] ?? ''); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Agama PDDikti</label>
                    <?php dosen_render_ref_select('id_agama_feeder', $ref_agama, $data['id_agama_feeder'] ?? '', '-- Pilih Agama --'); ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Status Dosen</label>
                    <select name="status_dosen" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="tetap" <?= ($data['status_dosen'] ?? '') == 'tetap' ? 'selected' : ''; ?>>Tetap</option>
                        <option value="tidak tetap" <?= ($data['status_dosen'] ?? '') == 'tidak tetap' ? 'selected' : ''; ?>>Tidak Tetap</option>
                        <option value="honorer" <?= ($data['status_dosen'] ?? '') == 'honorer' ? 'selected' : ''; ?>>Honorer</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Status Aktif PDDikti</label>
                    <?php dosen_render_ref_select('id_status_aktif_feeder', $ref_status_aktif, $data['id_status_aktif_feeder'] ?? '', '-- Pilih Status Aktif --'); ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Ikatan Kerja PDDikti</label>
                    <?php dosen_render_ref_select('id_ikatan_kerja_feeder', $ref_ikatan, $data['id_ikatan_kerja_feeder'] ?? '', '-- Pilih Ikatan Kerja --'); ?>
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

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat</label>
                    <textarea name="alamat" rows="4"
                              class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none"><?= htmlspecialchars($data['alamat'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($data['username'] ?? ''); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    <?php if (empty($data['id_user'])): ?>
                        <p class="text-xs text-slate-500 mt-2">Kosongkan jika dosen hasil pull belum perlu dibuatkan akun login.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Password Baru</label>
                    <input type="text" name="password" placeholder="Kosongkan jika tidak diubah"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Foto Dosen</label>

                    <div class="flex items-center gap-4 mb-3">
                        <?php if (!empty($data['foto'])): ?>
                            <img src="../../uploads/dosen/<?= htmlspecialchars($data['foto']); ?>"
                                 alt="Foto Dosen"
                                 class="w-16 h-16 rounded-full object-cover border border-slate-200">
                        <?php else: ?>
                            <div class="w-16 h-16 rounded-full bg-blue-700 text-white flex items-center justify-center text-xl font-bold">
                                <?= strtoupper(substr($data['nama_dosen'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>

                        <div>
                            <p class="text-sm font-semibold text-slate-700">Foto saat ini</p>
                            <p class="text-xs text-slate-500">Kosongkan jika tidak ingin mengganti foto.</p>
                        </div>
                    </div>

                    <input type="file" name="foto" accept="image/png, image/jpeg, image/jpg"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    <p class="text-xs text-slate-500 mt-2">Format JPG, JPEG, PNG. Maksimal 2MB.</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Status Akun</label>
                    <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="aktif" <?= $data['status'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="nonaktif" <?= $data['status'] == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                </div>

                <div class="lg:col-span-2 flex flex-col sm:flex-row gap-3 pt-4">
                    <button type="submit"
                            class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-save mr-2"></i>
                        Update
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

            <h3 class="text-lg font-bold text-slate-800 mb-4">Informasi Dosen</h3>

            <div class="space-y-4 text-sm text-slate-600">
                <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                    <p class="font-semibold text-blue-700 mb-1">Program Studi Saat Ini</p>
                    <p><?= htmlspecialchars($data['nama_prodi'] ?? '-'); ?> - <?= htmlspecialchars($data['jenjang'] ?? '-'); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-green-50 border border-green-100">
                    <p class="font-semibold text-green-700 mb-1">Akun Login</p>
                    <p>Username dan data kontak dosen akan ikut diperbarui pada tabel pengguna.</p>
                </div>

                <div class="p-4 rounded-xl bg-orange-50 border border-orange-100">
                    <p class="font-semibold text-orange-700 mb-1">Notifikasi</p>
                    <p>Email, WhatsApp, dan notifikasi internal akan dikirim setelah data diperbarui.</p>
                </div>
            </div>

            <div class="mt-6 space-y-3">
                <a href="detail_dosen.php?id=<?= $data['id_dosen']; ?>"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold">
                    <i class="fa-solid fa-eye mr-2"></i>
                    Detail Dosen
                </a>

                <a href="data_dosen.php"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold">
                    <i class="fa-solid fa-list mr-2"></i>
                    Data Dosen
                </a>
            </div>

        </aside>

    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
