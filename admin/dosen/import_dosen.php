<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Import Data Dosen";
$page_subtitle = "Import data dosen melalui file Excel";

$role_dosen = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT id_role FROM roles 
    WHERE nama_role = 'dosen'
    LIMIT 1
"));

$id_role_dosen = $role_dosen['id_role'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($id_role_dosen <= 0) {
        set_alert("error", "Role dosen belum tersedia pada tabel roles.");
    } elseif (empty($_FILES['file_excel']['name'])) {
        set_alert("error", "File Excel wajib diunggah.");
    } else {

        $file_tmp = $_FILES['file_excel']['tmp_name'];
        $file_name = $_FILES['file_excel']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($ext, ['xls', 'xlsx'])) {
            set_alert("error", "Format file harus .xls atau .xlsx.");
        } else {

            try {
                $spreadsheet = IOFactory::load($file_tmp);
                $sheet = $spreadsheet->getActiveSheet()->toArray();

                if (count($sheet) < 2) {
                    set_alert("error", "File Excel tidak memiliki data.");
                } else {

                    $berhasil = 0;
                    $gagal = 0;
                    $pesan_gagal = [];

                    mysqli_begin_transaction($conn);

                    for ($i = 1; $i < count($sheet); $i++) {

                        $row = $sheet[$i];

                        $id_prodi_excel = intval($row[0] ?? 0);
                        $nidn = trim($row[1] ?? '');
                        $nip = trim($row[2] ?? '');
                        $nama_dosen = trim($row[3] ?? '');
                        $gelar_depan = trim($row[4] ?? '');
                        $gelar_belakang = trim($row[5] ?? '');
                        $jenis_kelamin = trim($row[6] ?? '');
                        $tempat_lahir = trim($row[7] ?? '');
                        $tanggal_lahir = trim($row[8] ?? '');
                        $email = trim($row[9] ?? '');
                        $no_hp = trim($row[10] ?? '');
                        $alamat = trim($row[11] ?? '');
                        $status_dosen = trim($row[12] ?? 'tetap');
                        $username = trim($row[13] ?? '');
                        $password = trim($row[14] ?? '123456');

                        $baris = $i + 1;

                        if ($id_prodi_excel <= 0 || empty($nama_dosen) || empty($username)) {
                            $gagal++;
                            $pesan_gagal[] = "Baris $baris: id_prodi, nama dosen, dan username wajib diisi.";
                            continue;
                        }

                        $q_prodi = mysqli_query($conn, "
                            SELECT id_prodi 
                            FROM prodi 
                            WHERE id_prodi = '$id_prodi_excel'
                            LIMIT 1
                        ");

                        if (mysqli_num_rows($q_prodi) < 1) {
                            $gagal++;
                            $pesan_gagal[] = "Baris $baris: id_prodi $id_prodi_excel tidak ditemukan pada tabel prodi.";
                            continue;
                        }

                        $prodi = mysqli_fetch_assoc($q_prodi);
                        $id_prodi = intval($prodi['id_prodi']);

                        $username_db = mysqli_real_escape_string($conn, $username);

                        $cek_user = mysqli_query($conn, "
                            SELECT id_user FROM users 
                            WHERE username = '$username_db'
                            LIMIT 1
                        ");

                        if (mysqli_num_rows($cek_user) > 0) {
                            $gagal++;
                            $pesan_gagal[] = "Baris $baris: id_prodi $id_prodi_excel tidak ditemukan pada tabel prodi.";
                            continue;
                        }

                        if (!empty($nidn)) {
                            $nidn_db = mysqli_real_escape_string($conn, $nidn);
                            $cek_nidn = mysqli_query($conn, "
                                SELECT id_dosen FROM dosen 
                                WHERE nidn = '$nidn_db'
                                LIMIT 1
                            ");

                            if (mysqli_num_rows($cek_nidn) > 0) {
                                $gagal++;
                                $pesan_gagal[] = "Baris $baris: NIDN $nidn sudah digunakan.";
                                continue;
                            }
                        }

                        if (!in_array($jenis_kelamin, ['L', 'P', ''])) {
                            $gagal++;
                            $pesan_gagal[] = "Baris $baris: jenis kelamin harus L atau P.";
                            continue;
                        }

                        if (!in_array($status_dosen, ['tetap', 'tidak tetap', 'honorer'])) {
                            $status_dosen = 'tetap';
                        }

                        $password_hash = password_hash($password, PASSWORD_DEFAULT);

                        $nama_dosen_db = mysqli_real_escape_string($conn, $nama_dosen);
                        $email_db = mysqli_real_escape_string($conn, $email);
                        $no_hp_db = mysqli_real_escape_string($conn, $no_hp);
                        $nip_db = mysqli_real_escape_string($conn, $nip);
                        $gelar_depan_db = mysqli_real_escape_string($conn, $gelar_depan);
                        $gelar_belakang_db = mysqli_real_escape_string($conn, $gelar_belakang);
                        $jenis_kelamin_db = mysqli_real_escape_string($conn, $jenis_kelamin);
                        $tempat_lahir_db = mysqli_real_escape_string($conn, $tempat_lahir);
                        $alamat_db = mysqli_real_escape_string($conn, $alamat);
                        $status_dosen_db = mysqli_real_escape_string($conn, $status_dosen);

                        $tanggal_lahir_sql = "NULL";
                        if (!empty($tanggal_lahir)) {
                            $timestamp = strtotime($tanggal_lahir);
                            if ($timestamp) {
                                $tanggal_lahir_sql = "'" . date('Y-m-d', $timestamp) . "'";
                            }
                        }

                        $simpan_user = mysqli_query($conn, "
                            INSERT INTO users
                            (id_role, username, password, nama_lengkap, email, no_hp, status)
                            VALUES
                            ('$id_role_dosen', '$username_db', '$password_hash', '$nama_dosen_db', '$email_db', '$no_hp_db', 'aktif')
                        ");

                        if (!$simpan_user) {
                            $gagal++;
                            $pesan_gagal[] = "Baris $baris: gagal membuat akun pengguna.";
                            continue;
                        }

                        $id_user_baru = mysqli_insert_id($conn);

                        $simpan_dosen = mysqli_query($conn, "
                            INSERT INTO dosen
                            (
                                id_user, id_prodi, nidn, nip, nama_dosen,
                                gelar_depan, gelar_belakang, jenis_kelamin,
                                tempat_lahir, tanggal_lahir, email, no_hp,
                                alamat, status_dosen, status
                            )
                            VALUES
                            (
                                '$id_user_baru', '$id_prodi', '$nidn_db', '$nip_db', '$nama_dosen_db',
                                '$gelar_depan_db', '$gelar_belakang_db', '$jenis_kelamin_db',
                                '$tempat_lahir_db', $tanggal_lahir_sql, '$email_db', '$no_hp_db',
                                '$alamat_db', '$status_dosen_db', 'aktif'
                            )
                        ");

                        if (!$simpan_dosen) {
                            mysqli_query($conn, "DELETE FROM users WHERE id_user = '$id_user_baru'");
                            $gagal++;
                            $pesan_gagal[] = "Baris $baris: gagal menyimpan data dosen.";
                            continue;
                        }

                        $berhasil++;
                    }

                    mysqli_commit($conn);

                    simpan_log(
                        $conn,
                        $_SESSION['id_user'],
                        "Import data dosen. Berhasil: $berhasil, Gagal: $gagal",
                        "Dosen"
                    );

                    if ($gagal > 0) {
                        set_alert("warning", "Import selesai. Berhasil: $berhasil, Gagal: $gagal. Periksa catatan error pada file.");
                        $_SESSION['import_error_dosen'] = $pesan_gagal;
                    } else {
                        set_alert("success", "Import data dosen berhasil. Total data masuk: $berhasil.");
                    }

                    $_SESSION['import_summary_dosen'] = [
                        'total' => $berhasil + $gagal,
                        'berhasil' => $berhasil,
                        'gagal' => $gagal
                    ];

                    header("Location: import_dosen.php");
                    exit;
                }

            } catch (Exception $e) {
                mysqli_rollback($conn);
                set_alert("error", "Gagal membaca file Excel: " . $e->getMessage());
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

    <?php if (!empty($_SESSION['import_summary_dosen'])): ?>
        <?php $summary = $_SESSION['import_summary_dosen']; ?>

        <section class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Total Data Dibaca</p>
                <h2 class="text-3xl font-bold text-blue-700 mt-2">
                    <?= number_format($summary['total']); ?>
                </h2>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Berhasil Import</p>
                <h2 class="text-3xl font-bold text-green-700 mt-2">
                    <?= number_format($summary['berhasil']); ?>
                </h2>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Gagal Import</p>
                <h2 class="text-3xl font-bold text-red-700 mt-2">
                    <?= number_format($summary['gagal']); ?>
                </h2>
            </div>

        </section>

        <?php unset($_SESSION['import_summary_dosen']); ?>
    <?php endif; ?>

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Import Data Dosen</h2>
            <p class="text-sm text-slate-500">
                Unggah file Excel untuk memasukkan data dosen secara massal.
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

            <form method="POST" enctype="multipart/form-data" class="space-y-5">

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        File Excel
                    </label>

                    <input type="file" name="file_excel" accept=".xls,.xlsx" required
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

                    <p class="text-xs text-slate-500 mt-2">
                        Format file harus .xls atau .xlsx.
                    </p>
                </div>
                <div class="lg:col-span-2 flex flex-col sm:flex-row gap-3 pt-4">

                    <button type="submit" onclick="return confirm('Yakin ingin mengimport data dosen dari file ini?')"
                        class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-file-import mr-2"></i>
                        Import Data
                    </button>

                    <a href="template_import_dosen.php"
                        class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                        <i class="fa-solid fa-download mr-2"></i>
                        Download Template
                    </a>

                    <a href="data_dosen.php"
                        class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Kembali
                    </a>
                </div>
            </form>

            <?php if (!empty($_SESSION['import_error_dosen'])): ?>
                <div class="mt-6 p-4 rounded-xl bg-yellow-50 border border-yellow-200">
                    <h3 class="font-bold text-yellow-700 mb-3">
                        Catatan Data Gagal
                    </h3>

                    <ul class="list-disc pl-5 text-sm text-yellow-700 space-y-1">
                        <?php foreach ($_SESSION['import_error_dosen'] as $error): ?>
                            <li><?= htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <?php unset($_SESSION['import_error_dosen']); ?>
            <?php endif; ?>

        </div>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <h3 class="text-lg font-bold text-slate-800 mb-4">
                Format Kolom Excel
            </h3>

            <div class="overflow-x-auto rounded-xl border border-slate-200 mb-4">
                <table class="min-w-full text-xs">
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="px-3 py-2 font-semibold">A</td>
                            <td class="px-3 py-2">id_prodi</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">B</td>
                            <td class="px-3 py-2">nidn</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">C</td>
                            <td class="px-3 py-2">nip</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">D</td>
                            <td class="px-3 py-2">nama_dosen</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">E</td>
                            <td class="px-3 py-2">gelar_depan</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">F</td>
                            <td class="px-3 py-2">gelar_belakang</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">G</td>
                            <td class="px-3 py-2">jenis_kelamin</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">H</td>
                            <td class="px-3 py-2">tempat_lahir</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">I</td>
                            <td class="px-3 py-2">tanggal_lahir</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">J</td>
                            <td class="px-3 py-2">email</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">K</td>
                            <td class="px-3 py-2">no_hp</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">L</td>
                            <td class="px-3 py-2">alamat</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">M</td>
                            <td class="px-3 py-2">status_dosen</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">N</td>
                            <td class="px-3 py-2">username</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">O</td>
                            <td class="px-3 py-2">password</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="text-sm text-slate-600 space-y-3">
                <p><strong>jenis_kelamin:</strong> L atau P</p>
                <p><strong>status_dosen:</strong> tetap, tidak tetap, honorer</p>
                <p><strong>tanggal_lahir:</strong> gunakan format YYYY-MM-DD</p>
                <p><strong>id_prodi:</strong> harus sesuai dengan id pada tabel prodi.</p>
            </div>

        </aside>

    </section>

</main>

<?php require_once "../../includes/footer.php"; ?>