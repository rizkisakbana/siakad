<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/upload.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../includes/notification.php";
require_once "../../includes/email_gateway.php";
require_once "../../includes/whatsapp_gateway.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Edit Mahasiswa";
$link_login = "http://localhost/siakad-atitb/auth/login.php";

$id_mahasiswa = intval($_GET['id'] ?? 0);

if ($id_mahasiswa <= 0) {
    set_alert("error", "ID mahasiswa tidak valid.");
    header("Location: data_mahasiswa.php");
    exit;
}

function get_ref_options($conn, $jenis_ref)
{
    $jenis_ref = mysqli_real_escape_string($conn, $jenis_ref);
    return mysqli_query($conn, "
        SELECT *
        FROM ref_pddikti
        WHERE jenis_ref = '$jenis_ref'
        AND status = 'aktif'
        ORDER BY nama_ref ASC
    ");
}

function get_ref_name($conn, $jenis_ref, $id_feeder)
{
    if (empty($id_feeder))
        return '';

    $jenis_ref = mysqli_real_escape_string($conn, $jenis_ref);
    $id_feeder = mysqli_real_escape_string($conn, $id_feeder);

    $q = mysqli_query($conn, "
        SELECT nama_ref
        FROM ref_pddikti
        WHERE jenis_ref = '$jenis_ref'
        AND id_feeder = '$id_feeder'
        LIMIT 1
    ");

    if ($q && mysqli_num_rows($q) > 0) {
        return mysqli_fetch_assoc($q)['nama_ref'];
    }

    return '';
}

function get_table_columns($conn, $table)
{
    $columns = [];
    $q = mysqli_query($conn, "SHOW COLUMNS FROM $table");

    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $columns[] = $row['Field'];
        }
    }

    return $columns;
}

function update_assoc($conn, $table, $data, $where)
{
    $columns_available = get_table_columns($conn, $table);
    $sets = [];

    foreach ($data as $column => $value) {
        if (!in_array($column, $columns_available)) {
            continue;
        }

        if ($value === null || $value === '') {
            $sets[] = "$column = NULL";
        } else {
            $sets[] = "$column = '" . mysqli_real_escape_string($conn, $value) . "'";
        }
    }

    if (empty($sets)) {
        return false;
    }

    $sql = "
        UPDATE $table SET
            " . implode(", ", $sets) . "
        WHERE $where
    ";

    return mysqli_query($conn, $sql);
}

$query = mysqli_query($conn, "
    SELECT 
        mahasiswa.*,
        users.username,
        users.email AS email_user,
        users.no_hp AS no_hp_user,
        users.status AS status_user
    FROM mahasiswa
    LEFT JOIN users ON mahasiswa.id_user = users.id_user
    WHERE mahasiswa.id_mahasiswa = '$id_mahasiswa'
    LIMIT 1
");

if (!$query || mysqli_num_rows($query) < 1) {
    set_alert("error", "Data mahasiswa tidak ditemukan.");
    header("Location: data_mahasiswa.php");
    exit;
}

$data = mysqli_fetch_assoc($query);
$id_user_mahasiswa = intval($data['id_user'] ?? 0);

$role_mahasiswa = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT id_role
    FROM roles
    WHERE nama_role = 'mahasiswa'
    LIMIT 1
"));

$id_role_mahasiswa = intval($role_mahasiswa['id_role'] ?? 0);

$data_prodi = mysqli_query($conn, "
    SELECT *
    FROM prodi
    WHERE status = 'aktif'
    ORDER BY nama_prodi ASC
");

$data_kelas = mysqli_query($conn, "
    SELECT 
        kelas.*,
        prodi.nama_prodi
    FROM kelas
    LEFT JOIN prodi ON kelas.id_prodi = prodi.id_prodi
    WHERE kelas.status = 'aktif'
    ORDER BY prodi.nama_prodi ASC, kelas.nama_kelas ASC
");

$q_agama = get_ref_options($conn, 'agama');
$q_negara = get_ref_options($conn, 'negara');
$q_jalur_masuk = get_ref_options($conn, 'jalur_masuk');
$q_jenis_pendaftaran = get_ref_options($conn, 'jenis_pendaftaran');
$q_status_mahasiswa = get_ref_options($conn, 'status_mahasiswa');
$q_wilayah = get_ref_options($conn, 'wilayah');
$q_pekerjaan_ayah = get_ref_options($conn, 'pekerjaan');
$q_pekerjaan_ibu = get_ref_options($conn, 'pekerjaan');
$q_penghasilan_ayah = get_ref_options($conn, 'penghasilan');
$q_penghasilan_ibu = get_ref_options($conn, 'penghasilan');
$q_transportasi = get_ref_options($conn, 'alat_transportasi');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_prodi = intval($_POST['id_prodi'] ?? 0);
    $id_kelas = intval($_POST['id_kelas'] ?? 0);

    $nim = trim($_POST['nim'] ?? '');
    $nama_mahasiswa = trim($_POST['nama_mahasiswa'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;

    $nik = trim($_POST['nik'] ?? '');
    $nisn = trim($_POST['nisn'] ?? '');
    $npwp = trim($_POST['npwp'] ?? '');

    $alamat = trim($_POST['alamat'] ?? '');
    $kode_pos = trim($_POST['kode_pos'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');

    $asal_sekolah = trim($_POST['asal_sekolah'] ?? '');
    $tahun_lulus = trim($_POST['tahun_lulus'] ?? '');

    $nama_ayah = trim($_POST['nama_ayah'] ?? '');
    $nama_ibu = trim($_POST['nama_ibu'] ?? '');

    $angkatan = trim($_POST['angkatan'] ?? '');
    $semester = intval($_POST['semester'] ?? 1);
    $tanggal_masuk = $_POST['tanggal_masuk'] ?? null;
    $tanggal_keluar = $_POST['tanggal_keluar'] ?? null;

    $status = $_POST['status'] ?? 'aktif';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $id_agama_feeder = $_POST['id_agama_feeder'] ?? '';
    $id_negara_feeder = $_POST['id_negara_feeder'] ?? 'ID';
    $id_wilayah_feeder = $_POST['id_wilayah_feeder'] ?? '';
    $id_jalur_masuk_feeder = $_POST['id_jalur_masuk_feeder'] ?? '';
    $id_jenis_pendaftaran_feeder = $_POST['id_jenis_pendaftaran_feeder'] ?? '';
    $id_status_mahasiswa_feeder = $_POST['id_status_mahasiswa_feeder'] ?? '';
    $id_pekerjaan_ayah_feeder = $_POST['id_pekerjaan_ayah_feeder'] ?? '';
    $id_pekerjaan_ibu_feeder = $_POST['id_pekerjaan_ibu_feeder'] ?? '';
    $id_penghasilan_ayah_feeder = $_POST['id_penghasilan_ayah_feeder'] ?? '';
    $id_penghasilan_ibu_feeder = $_POST['id_penghasilan_ibu_feeder'] ?? '';
    $id_alat_transportasi_feeder = $_POST['id_alat_transportasi_feeder'] ?? '';

    $agama = get_ref_name($conn, 'agama', $id_agama_feeder);
    $kewarganegaraan = get_ref_name($conn, 'negara', $id_negara_feeder);
    $jalur_masuk = get_ref_name($conn, 'jalur_masuk', $id_jalur_masuk_feeder);
    $jenis_pendaftaran = get_ref_name($conn, 'jenis_pendaftaran', $id_jenis_pendaftaran_feeder);
    $status_mahasiswa = strtolower(get_ref_name($conn, 'status_mahasiswa', $id_status_mahasiswa_feeder));
    $pekerjaan_ayah = get_ref_name($conn, 'pekerjaan', $id_pekerjaan_ayah_feeder);
    $pekerjaan_ibu = get_ref_name($conn, 'pekerjaan', $id_pekerjaan_ibu_feeder);
    $penghasilan_ayah = get_ref_name($conn, 'penghasilan', $id_penghasilan_ayah_feeder);
    $penghasilan_ibu = get_ref_name($conn, 'penghasilan', $id_penghasilan_ibu_feeder);

    if (empty($status_mahasiswa))
        $status_mahasiswa = 'aktif';
    if (empty($kewarganegaraan))
        $kewarganegaraan = 'Indonesia';

    if ($id_prodi <= 0 || empty($nim) || empty($nama_mahasiswa) || empty($username)) {
        set_alert("error", "Program studi, NIM, nama mahasiswa, dan username wajib diisi.");
    } elseif ($semester < 1 || $semester > 14) {
        set_alert("error", "Semester tidak valid.");
    } else {

        $nim_db = mysqli_real_escape_string($conn, $nim);
        $username_db = mysqli_real_escape_string($conn, $username);
        $email_db = mysqli_real_escape_string($conn, $email);
        $nik_db = mysqli_real_escape_string($conn, $nik);

        $cek_user = mysqli_query($conn, "
            SELECT id_user
            FROM users
            WHERE username = '$username_db'
            AND id_user != '$id_user_mahasiswa'
            LIMIT 1
        ");

        $cek_email_user = false;
        if (!empty($email)) {
            $cek_email_user = mysqli_query($conn, "
                SELECT id_user
                FROM users
                WHERE email = '$email_db'
                AND id_user != '$id_user_mahasiswa'
                LIMIT 1
            ");
        }

        $cek_nim = mysqli_query($conn, "
            SELECT id_mahasiswa
            FROM mahasiswa
            WHERE nim = '$nim_db'
            AND id_mahasiswa != '$id_mahasiswa'
            LIMIT 1
        ");

        $cek_nik = false;
        if (!empty($nik)) {
            $cek_nik = mysqli_query($conn, "
                SELECT id_mahasiswa
                FROM mahasiswa
                WHERE nik = '$nik_db'
                AND id_mahasiswa != '$id_mahasiswa'
                LIMIT 1
            ");
        }

        if ($cek_user && mysqli_num_rows($cek_user) > 0) {
            set_alert("error", "Username sudah digunakan.");
        } elseif ($cek_email_user && mysqli_num_rows($cek_email_user) > 0) {
            set_alert("error", "Email sudah digunakan oleh akun pengguna lain.");
        } elseif ($cek_nim && mysqli_num_rows($cek_nim) > 0) {
            set_alert("error", "NIM sudah digunakan.");
        } elseif ($cek_nik && mysqli_num_rows($cek_nik) > 0) {
            set_alert("error", "NIK sudah digunakan.");
        } else {

            $foto = $data['foto'] ?? null;

            if (!empty($_FILES['foto']['name'])) {
                $upload = upload_file(
                    $_FILES['foto'],
                    "../../uploads/mahasiswa",
                    ['jpg', 'jpeg', 'png'],
                    2097152
                );

                if (!$upload['status']) {
                    set_alert("error", $upload['message']);
                } else {
                    if (!empty($foto) && file_exists("../../uploads/mahasiswa/" . $foto)) {
                        unlink("../../uploads/mahasiswa/" . $foto);
                    }

                    $foto = $upload['filename'];
                }
            }

            if (!isset($_SESSION['alert'])) {
                mysqli_begin_transaction($conn);

                try {
                    $nama_db = mysqli_real_escape_string($conn, $nama_mahasiswa);
                    $no_hp_db = mysqli_real_escape_string($conn, $no_hp);
                    $foto_db = mysqli_real_escape_string($conn, $foto ?? '');
                    $status_db = mysqli_real_escape_string($conn, $status);

                    $password_sql = "";
                    if (!empty($password)) {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $password_sql = ", password = '" . mysqli_real_escape_string($conn, $password_hash) . "'";
                    }

                    if ($id_user_mahasiswa > 0) {
                        $email_sql = !empty($email) ? "'$email_db'" : "NULL";
                        $no_hp_sql = !empty($no_hp) ? "'$no_hp_db'" : "NULL";

                        $update_user = mysqli_query($conn, "
                            UPDATE users SET
                                username = '$username_db',
                                nama_lengkap = '$nama_db',
                                email = $email_sql,
                                no_hp = $no_hp_sql,
                                status = '$status_db',
                                foto = '$foto_db'
                                $password_sql
                            WHERE id_user = '$id_user_mahasiswa'
                        ");

                        if (!$update_user) {
                            throw new Exception("Gagal memperbarui akun pengguna: " . mysqli_error($conn));
                        }
                    } else {
                        if ($id_role_mahasiswa <= 0) {
                            throw new Exception("Role mahasiswa belum tersedia pada tabel roles.");
                        }

                        if (empty($password)) {
                            throw new Exception("Akun pengguna mahasiswa belum terhubung. Isi password baru untuk membuat akun login.");
                        }

                        $email_sql = !empty($email) ? "'$email_db'" : "NULL";
                        $no_hp_sql = !empty($no_hp) ? "'$no_hp_db'" : "NULL";

                        $simpan_user = mysqli_query($conn, "
                            INSERT INTO users
                            (id_role, username, password, nama_lengkap, email, no_hp, foto, status)
                            VALUES
                            ('$id_role_mahasiswa', '$username_db', '$password_hash', '$nama_db', $email_sql, $no_hp_sql, '$foto_db', '$status_db')
                        ");

                        if (!$simpan_user) {
                            throw new Exception("Gagal membuat akun pengguna mahasiswa: " . mysqli_error($conn));
                        }

                        $id_user_mahasiswa = mysqli_insert_id($conn);
                    }

                    $data_mahasiswa = [
                        'id_user' => $id_user_mahasiswa,
                        'id_prodi' => $id_prodi,
                        'id_kelas' => $id_kelas > 0 ? $id_kelas : null,

                        'nim' => $nim,
                        'nama_mahasiswa' => $nama_mahasiswa,
                        'jenis_kelamin' => $jenis_kelamin,
                        'tempat_lahir' => $tempat_lahir,
                        'tanggal_lahir' => !empty($tanggal_lahir) ? $tanggal_lahir : null,

                        'agama' => $agama,
                        'id_agama_feeder' => $id_agama_feeder,

                        'nik' => $nik,
                        'nisn' => $nisn,
                        'npwp' => $npwp,

                        'kewarganegaraan' => $kewarganegaraan,
                        'id_negara_feeder' => $id_negara_feeder,

                        'alamat' => $alamat,
                        'kode_pos' => $kode_pos,
                        'id_wilayah_feeder' => $id_wilayah_feeder,

                        'email' => $email,
                        'no_hp' => $no_hp,

                        'asal_sekolah' => $asal_sekolah,
                        'tahun_lulus' => !empty($tahun_lulus) ? $tahun_lulus : null,

                        'nama_ayah' => $nama_ayah,
                        'nama_ibu' => $nama_ibu,

                        'pekerjaan_ayah' => $pekerjaan_ayah,
                        'id_pekerjaan_ayah_feeder' => $id_pekerjaan_ayah_feeder,

                        'pekerjaan_ibu' => $pekerjaan_ibu,
                        'id_pekerjaan_ibu_feeder' => $id_pekerjaan_ibu_feeder,

                        'penghasilan_ayah' => $penghasilan_ayah,
                        'id_penghasilan_ayah_feeder' => $id_penghasilan_ayah_feeder,

                        'penghasilan_ibu' => $penghasilan_ibu,
                        'id_penghasilan_ibu_feeder' => $id_penghasilan_ibu_feeder,

                        'penghasilan_ortu' => trim($penghasilan_ayah . ' / ' . $penghasilan_ibu, ' /'),

                        'id_alat_transportasi_feeder' => $id_alat_transportasi_feeder,

                        'foto' => $foto,

                        'status_mahasiswa' => $status_mahasiswa,
                        'id_status_mahasiswa_feeder' => $id_status_mahasiswa_feeder,

                        'angkatan' => !empty($angkatan) ? $angkatan : null,
                        'semester' => $semester,

                        'jalur_masuk' => $jalur_masuk,
                        'id_jalur_masuk_feeder' => $id_jalur_masuk_feeder,

                        'jenis_pendaftaran' => $jenis_pendaftaran,
                        'id_jenis_pendaftaran_feeder' => $id_jenis_pendaftaran_feeder,

                        'tanggal_masuk' => !empty($tanggal_masuk) ? $tanggal_masuk : null,
                        'tanggal_keluar' => !empty($tanggal_keluar) ? $tanggal_keluar : null,

                        'status' => $status,

                        'status_sync_feeder' => 'belum',
                        'last_sync_feeder' => null,
                        'last_error_feeder' => null
                    ];

                    $update_mahasiswa = update_assoc(
                        $conn,
                        'mahasiswa',
                        $data_mahasiswa,
                        "id_mahasiswa = '$id_mahasiswa'"
                    );

                    if (!$update_mahasiswa) {
                        throw new Exception("Gagal memperbarui data mahasiswa: " . mysqli_error($conn));
                    }

                    mysqli_commit($conn);

                    simpan_log(
                        $conn,
                        $_SESSION['id_user'],
                        "Mengubah data mahasiswa: $nim - $nama_mahasiswa",
                        "Mahasiswa"
                    );

                    if ($id_user_mahasiswa > 0) {
                        kirim_notifikasi(
                            $conn,
                            $id_user_mahasiswa,
                            "Data Mahasiswa Diperbarui",
                            "Data mahasiswa Anda telah diperbarui oleh administrator.",
                            "info",
                            "../mahasiswa/dashboard.php"
                        );
                    }

                    if (!empty($email)) {
                        $pesan_email = "
                            <p>Yth. <strong>$nama_mahasiswa</strong>,</p>
                            <p>Data mahasiswa dan akun SIAKAD Anda telah diperbarui oleh administrator.</p>
                            <p><strong>NIM:</strong> $nim<br>
                            <strong>Username:</strong> $username<br>
                            <strong>Status Akun:</strong> $status</p>
                        ";

                        if (!empty($password)) {
                            $pesan_email .= "
                                <p>Password akun Anda juga telah diperbarui.</p>
                                <p><strong>Password Baru:</strong> $password</p>
                                <p>Demi keamanan, segera ubah password setelah berhasil login.</p>
                            ";
                        }

                        $pesan_email .= "
                            <p>Login: <a href='$link_login'>$link_login</a></p>
                            <p>Jika informasi ini tidak sesuai, silakan hubungi admin akademik.</p>
                        ";

                        kirim_email(
                            $conn,
                            $id_user_mahasiswa,
                            $email,
                            "Informasi Perubahan Data Mahasiswa SIAKAD ATITB",
                            $pesan_email
                        );
                    }

                    if (!empty($no_hp)) {
                        $pesan_wa = "*PERUBAHAN DATA MAHASISWA SIAKAD ATITB*\n\n" .
                            "Halo $nama_mahasiswa,\n\n" .
                            "Data mahasiswa dan akun SIAKAD Anda telah diperbarui oleh administrator.\n\n" .
                            "- NIM: $nim\n" .
                            "- Username: $username\n" .
                            "- Status Akun: $status\n";

                        if (!empty($password)) {
                            $pesan_wa .= "- Password Baru: $password\n";
                        }

                        $pesan_wa .= "\nLogin: $link_login\n\n" .
                            "Jika informasi ini tidak sesuai, silakan hubungi admin akademik.";

                        kirim_whatsapp(
                            $conn,
                            $id_user_mahasiswa,
                            $no_hp,
                            $pesan_wa
                        );
                    }

                    set_alert("success", "Data mahasiswa berhasil diperbarui. Status sinkron NeoFeeder dikembalikan menjadi belum.");
                    header("Location: detail_mahasiswa.php?id=" . $id_mahasiswa);
                    exit;

                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    set_alert("error", $e->getMessage());
                }
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
            <h2 class="text-xl font-bold text-slate-800">Edit Mahasiswa</h2>
            <p class="text-sm text-slate-500">Perbarui data mahasiswa dan referensi PDDikti.</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="detail_mahasiswa.php?id=<?= $id_mahasiswa; ?>"
               class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold">
                Detail
            </a>

            <a href="data_mahasiswa.php"
               class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                Kembali
            </a>
        </div>
    </div> -->

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <div class="lg:col-span-2">
                <h3 class="text-lg font-bold text-slate-800 mb-2">Data Akademik</h3>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Program Studi *</label>
                <select name="id_prodi" required class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">-- Pilih Prodi --</option>
                    <?php while ($prodi = mysqli_fetch_assoc($data_prodi)): ?>
                        <option value="<?= $prodi['id_prodi']; ?>" <?= ($data['id_prodi'] ?? '') == $prodi['id_prodi'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($prodi['nama_prodi']); ?> -
                            <?= htmlspecialchars($prodi['jenjang'] ?? ''); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Kelas</label>
                <select name="id_kelas" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">-- Belum Masuk Kelas --</option>
                    <?php while ($kelas = mysqli_fetch_assoc($data_kelas)): ?>
                        <option value="<?= $kelas['id_kelas']; ?>" <?= ($data['id_kelas'] ?? '') == $kelas['id_kelas'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($kelas['nama_kelas']); ?> - <?= htmlspecialchars($kelas['nama_prodi']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">NIM *</label>
                <input type="text" name="nim" required value="<?= htmlspecialchars($data['nim'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Angkatan</label>
                <input type="number" name="angkatan" value="<?= htmlspecialchars($data['angkatan'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Semester</label>
                <input type="number" name="semester" min="1" max="14"
                    value="<?= htmlspecialchars($data['semester'] ?? 1); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Masuk</label>
                <input type="date" name="tanggal_masuk" value="<?= htmlspecialchars($data['tanggal_masuk'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Jalur Masuk PDDikti</label>
                <select name="id_jalur_masuk_feeder" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">-- Pilih Jalur Masuk --</option>
                    <?php while ($row = mysqli_fetch_assoc($q_jalur_masuk)): ?>
                        <option value="<?= htmlspecialchars($row['id_feeder']); ?>" <?= ($data['id_jalur_masuk_feeder'] ?? '') == $row['id_feeder'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($row['nama_ref']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Jenis Pendaftaran PDDikti</label>
                <select name="id_jenis_pendaftaran_feeder" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">-- Pilih Jenis Pendaftaran --</option>
                    <?php while ($row = mysqli_fetch_assoc($q_jenis_pendaftaran)): ?>
                        <option value="<?= htmlspecialchars($row['id_feeder']); ?>" <?= ($data['id_jenis_pendaftaran_feeder'] ?? '') == $row['id_feeder'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($row['nama_ref']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Status Mahasiswa PDDikti</label>
                <select name="id_status_mahasiswa_feeder" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">-- Pilih Status Mahasiswa --</option>
                    <?php while ($row = mysqli_fetch_assoc($q_status_mahasiswa)): ?>
                        <option value="<?= htmlspecialchars($row['id_feeder']); ?>" <?= ($data['id_status_mahasiswa_feeder'] ?? '') == $row['id_feeder'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($row['nama_ref']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="lg:col-span-2 pt-4">
                <h3 class="text-lg font-bold text-slate-800 mb-2">Biodata Mahasiswa</h3>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Mahasiswa *</label>
                <input type="text" name="nama_mahasiswa" required
                    value="<?= htmlspecialchars($data['nama_mahasiswa'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">-- Pilih --</option>
                    <option value="L" <?= ($data['jenis_kelamin'] ?? '') == 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                    <option value="P" <?= ($data['jenis_kelamin'] ?? '') == 'P' ? 'selected' : ''; ?>>Perempuan</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Agama PDDikti</label>
                <select name="id_agama_feeder" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">-- Pilih Agama --</option>
                    <?php while ($row = mysqli_fetch_assoc($q_agama)): ?>
                        <option value="<?= htmlspecialchars($row['id_feeder']); ?>" <?= ($data['id_agama_feeder'] ?? '') == $row['id_feeder'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($row['nama_ref']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tempat Lahir</label>
                <input type="text" name="tempat_lahir" value="<?= htmlspecialchars($data['tempat_lahir'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" value="<?= htmlspecialchars($data['tanggal_lahir'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">NIK</label>
                <input type="text" name="nik" maxlength="16" value="<?= htmlspecialchars($data['nik'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">NISN</label>
                <input type="text" name="nisn" value="<?= htmlspecialchars($data['nisn'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">NPWP</label>
                <input type="text" name="npwp" value="<?= htmlspecialchars($data['npwp'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Negara / Kewarganegaraan</label>
                <select name="id_negara_feeder" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">-- Pilih Negara --</option>
                    <?php while ($row = mysqli_fetch_assoc($q_negara)): ?>
                        <option value="<?= htmlspecialchars($row['id_feeder']); ?>" <?= ($data['id_negara_feeder'] ?? 'ID') == $row['id_feeder'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($row['nama_ref']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat</label>
                <textarea name="alamat" rows="3"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3"><?= htmlspecialchars($data['alamat'] ?? ''); ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Wilayah PDDikti</label>

                <input type="hidden" name="id_wilayah_feeder" id="id_wilayah_feeder"
                    value="<?= htmlspecialchars($data['id_wilayah_feeder'] ?? ''); ?>">

                <?php
                $nama_wilayah_terpilih = '';

                if (!empty($data['id_wilayah_feeder'])) {
                    $id_wilayah_db = mysqli_real_escape_string($conn, $data['id_wilayah_feeder']);

                    $q_wilayah_terpilih = mysqli_query($conn, "
            SELECT nama_ref
            FROM ref_pddikti
            WHERE jenis_ref = 'wilayah'
            AND id_feeder = '$id_wilayah_db'
            LIMIT 1
        ");

                    if ($q_wilayah_terpilih && mysqli_num_rows($q_wilayah_terpilih) > 0) {
                        $wilayah_terpilih = mysqli_fetch_assoc($q_wilayah_terpilih);
                        $nama_wilayah_terpilih = $wilayah_terpilih['nama_ref'] . ' (' . $data['id_wilayah_feeder'] . ')';
                    }
                }
                ?>

                <div class="relative">
                    <input type="text" id="cari_wilayah" value="<?= htmlspecialchars($nama_wilayah_terpilih); ?>"
                        placeholder="Ketik minimal 2 huruf, contoh: Duren Sawit" autocomplete="off"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3">

                    <div id="hasil_wilayah"
                        class="hidden absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-64 overflow-y-auto">
                    </div>
                </div>

                <p class="text-xs text-slate-500 mt-1">
                    Kosongkan lalu ketik ulang jika ingin mengganti wilayah.
                </p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Kode Pos</label>
                <input type="text" name="kode_pos" value="<?= htmlspecialchars($data['kode_pos'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                <input type="email" name="email"
                    value="<?= htmlspecialchars($data['email'] ?? $data['email_user'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">No. HP</label>
                <input type="text" name="no_hp"
                    value="<?= htmlspecialchars($data['no_hp'] ?? $data['no_hp_user'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Alat Transportasi</label>
                <select name="id_alat_transportasi_feeder" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">-- Pilih Transportasi --</option>
                    <?php while ($row = mysqli_fetch_assoc($q_transportasi)): ?>
                        <option value="<?= htmlspecialchars($row['id_feeder']); ?>" <?= ($data['id_alat_transportasi_feeder'] ?? '') == $row['id_feeder'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($row['nama_ref']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Foto</label>
                <input type="file" name="foto" accept="image/png, image/jpeg, image/jpg"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
                <?php if (!empty($data['foto'])): ?>
                    <p class="text-xs text-slate-500 mt-2">Foto saat ini: <?= htmlspecialchars($data['foto']); ?></p>
                <?php endif; ?>
            </div>

            <div class="lg:col-span-2 pt-4">
                <h3 class="text-lg font-bold text-slate-800 mb-2">Asal Sekolah & Orang Tua</h3>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Asal Sekolah</label>
                <input type="text" name="asal_sekolah" value="<?= htmlspecialchars($data['asal_sekolah'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tahun Lulus</label>
                <input type="number" name="tahun_lulus" value="<?= htmlspecialchars($data['tahun_lulus'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Ayah</label>
                <input type="text" name="nama_ayah" value="<?= htmlspecialchars($data['nama_ayah'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Ibu</label>
                <input type="text" name="nama_ibu" value="<?= htmlspecialchars($data['nama_ibu'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Pekerjaan Ayah</label>
                <select name="id_pekerjaan_ayah_feeder" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">-- Pilih Pekerjaan Ayah --</option>
                    <?php while ($row = mysqli_fetch_assoc($q_pekerjaan_ayah)): ?>
                        <option value="<?= htmlspecialchars($row['id_feeder']); ?>" <?= ($data['id_pekerjaan_ayah_feeder'] ?? '') == $row['id_feeder'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($row['nama_ref']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Pekerjaan Ibu</label>
                <select name="id_pekerjaan_ibu_feeder" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">-- Pilih Pekerjaan Ibu --</option>
                    <?php while ($row = mysqli_fetch_assoc($q_pekerjaan_ibu)): ?>
                        <option value="<?= htmlspecialchars($row['id_feeder']); ?>" <?= ($data['id_pekerjaan_ibu_feeder'] ?? '') == $row['id_feeder'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($row['nama_ref']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Penghasilan Ayah</label>
                <select name="id_penghasilan_ayah_feeder" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">-- Pilih Penghasilan Ayah --</option>
                    <?php while ($row = mysqli_fetch_assoc($q_penghasilan_ayah)): ?>
                        <option value="<?= htmlspecialchars($row['id_feeder']); ?>" <?= ($data['id_penghasilan_ayah_feeder'] ?? '') == $row['id_feeder'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($row['nama_ref']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Penghasilan Ibu</label>
                <select name="id_penghasilan_ibu_feeder" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">-- Pilih Penghasilan Ibu --</option>
                    <?php while ($row = mysqli_fetch_assoc($q_penghasilan_ibu)): ?>
                        <option value="<?= htmlspecialchars($row['id_feeder']); ?>" <?= ($data['id_penghasilan_ibu_feeder'] ?? '') == $row['id_feeder'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($row['nama_ref']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="lg:col-span-2 pt-4">
                <h3 class="text-lg font-bold text-slate-800 mb-2">Akun Login</h3>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Username *</label>
                <input type="text" name="username" required value="<?= htmlspecialchars($data['username'] ?? ''); ?>"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Password Baru</label>
                <input type="text" name="password" placeholder="Kosongkan jika tidak diubah"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Status Akun</label>
                <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="aktif" <?= ($data['status_user'] ?? $data['status'] ?? '') == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="nonaktif" <?= ($data['status_user'] ?? $data['status'] ?? '') == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif
                    </option>
                </select>
            </div>

            <div class="lg:col-span-2 flex flex-col sm:flex-row gap-3 pt-4">
                <button type="submit"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                    <i class="fa fa-save mr-2"></i>
                    Update
                </button>

                <a href="detail_mahasiswa.php?id=<?= $id_mahasiswa; ?>"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa fa-arrow-left mr-2"></i>
                    Kembali
                </a>
            </div>

        </form>

    </section>

</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('cari_wilayah');
        const hidden = document.getElementById('id_wilayah_feeder');
        const resultBox = document.getElementById('hasil_wilayah');

        let timer = null;

        input.addEventListener('keyup', function () {
            const q = input.value.trim();

            hidden.value = '';

            if (q.length < 2) {
                resultBox.classList.add('hidden');
                resultBox.innerHTML = '';
                return;
            }

            clearTimeout(timer);

            timer = setTimeout(function () {
                fetch('ajax_cari_wilayah.php?q=' + encodeURIComponent(q))
                    .then(response => response.json())
                    .then(data => {
                        resultBox.innerHTML = '';

                        if (data.length === 0) {
                            resultBox.innerHTML = `
                            <div class="px-4 py-3 text-sm text-slate-500">
                                Wilayah tidak ditemukan
                            </div>
                        `;
                            resultBox.classList.remove('hidden');
                            return;
                        }

                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'px-4 py-3 text-sm cursor-pointer hover:bg-blue-50 border-b border-slate-100';
                            div.textContent = item.text;

                            div.addEventListener('click', function () {
                                input.value = item.text;
                                hidden.value = item.id;
                                resultBox.classList.add('hidden');
                                resultBox.innerHTML = '';
                            });

                            resultBox.appendChild(div);
                        });

                        resultBox.classList.remove('hidden');
                    });
            }, 400);
        });

        document.addEventListener('click', function (e) {
            if (!input.contains(e.target) && !resultBox.contains(e.target)) {
                resultBox.classList.add('hidden');
            }
        });
    });
</script>

<?php require_once "../../includes/footer.php"; ?>
