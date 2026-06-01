<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/mahasiswa_helper.php";
require_once __DIR__ . "/../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Import Data Mahasiswa";
$page_subtitle = "Import data mahasiswa melalui file Excel";

/** @var mysqli $conn */

$role_mahasiswa = mahasiswa_query_one($conn, "
    SELECT id_role FROM roles 
    WHERE nama_role = 'mahasiswa'
    LIMIT 1
");

$id_role_mahasiswa = $role_mahasiswa['id_role'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($id_role_mahasiswa <= 0) {
        set_alert("error", "Role mahasiswa belum tersedia pada tabel roles.");
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

                    for ($i = 5; $i < count($sheet); $i++) {

                        $row = $sheet[$i];
                        $baris = $i + 1;

                        $isEmptyRow = true;

                        foreach ($row as $cell) {
                            if (trim((string) $cell) !== '') {
                                $isEmptyRow = false;
                                break;
                            }
                        }

                        if ($isEmptyRow) {
                            continue;
                        }

                        $id_prodi = intval($row[0] ?? 0);
                        $id_kelas = intval($row[1] ?? 0);
                        $nim = trim($row[2] ?? '');
                        $nama_mahasiswa = trim($row[3] ?? '');
                        $jenis_kelamin = trim($row[4] ?? '');
                        $tempat_lahir = trim($row[5] ?? '');
                        $tanggal_lahir = trim($row[6] ?? '');
                        $agama = trim($row[7] ?? '');
                        $nik = trim($row[8] ?? '');
                        $nisn = trim($row[9] ?? '');
                        $npwp = trim($row[10] ?? '');
                        $kewarganegaraan = trim($row[11] ?? 'Indonesia');
                        $alamat = trim($row[12] ?? '');
                        $kode_pos = trim($row[13] ?? '');
                        $email = trim($row[14] ?? '');
                        $no_hp = trim($row[15] ?? '');
                        $asal_sekolah = trim($row[16] ?? '');
                        $tahun_lulus = trim($row[17] ?? '');
                        $nama_ayah = trim($row[18] ?? '');
                        $nama_ibu = trim($row[19] ?? '');
                        $pekerjaan_ayah = trim($row[20] ?? '');
                        $pekerjaan_ibu = trim($row[21] ?? '');
                        $penghasilan_ortu = trim($row[22] ?? '');
                        $status_mahasiswa = trim($row[23] ?? 'aktif');
                        $angkatan = trim($row[24] ?? '');
                        $semester = intval($row[25] ?? 1);
                        $jalur_masuk = trim($row[26] ?? '');
                        $tanggal_masuk = trim($row[27] ?? '');
                        $tanggal_keluar = trim($row[28] ?? '');
                        $username = trim($row[29] ?? '');
                        $password = trim($row[30] ?? '123456');

                        if ($id_prodi <= 0 || empty($nim) || empty($nama_mahasiswa) || empty($username)) {
                            $gagal++;
                            $pesan_gagal[] = "Baris $baris: id_prodi, NIM, nama mahasiswa, dan username wajib diisi.";
                            continue;
                        }


                        $q_prodi = mahasiswa_query_one($conn, "
                            SELECT id_prodi 
                            FROM prodi 
                            WHERE id_prodi = '$id_prodi'
                            LIMIT 1
                        ");

                        if (!$q_prodi) {
                            $gagal++;
                            $pesan_gagal[] = "Baris $baris: id_prodi $id_prodi tidak ditemukan.";
                            continue;
                        }

                        if ($id_kelas > 0) {
                            $kelas = mahasiswa_query_one($conn, "
                                SELECT id_kelas, id_prodi 
                                FROM kelas
                                WHERE id_kelas = '$id_kelas'
                                LIMIT 1
                            ");

                            if (!$kelas) {
                                $gagal++;
                                $pesan_gagal[] = "Baris $baris: id_kelas $id_kelas tidak ditemukan.";
                                continue;
                            }

                            if (intval($kelas['id_prodi']) !== $id_prodi) {
                                $gagal++;
                                $pesan_gagal[] = "Baris $baris: id_kelas $id_kelas tidak sesuai dengan id_prodi $id_prodi.";
                                continue;
                            }
                        }

                        if (!in_array($jenis_kelamin, ['L', 'P', ''])) {
                            $gagal++;
                            $pesan_gagal[] = "Baris $baris: jenis_kelamin harus L atau P.";
                            continue;
                        }

                        $status_valid = ['aktif', 'cuti', 'nonaktif', 'lulus', 'drop out', 'mengundurkan diri', 'pindah'];
                        if (!in_array($status_mahasiswa, $status_valid)) {
                            $status_mahasiswa = 'aktif';
                        }

                        if ($semester < 1 || $semester > 14) {
                            $semester = 1;
                        }

                        $username_db = mysqli_real_escape_string($conn, $username);
                        $nim_db = mysqli_real_escape_string($conn, $nim);
                        $nik_db = mysqli_real_escape_string($conn, $nik);
                        $email_db = mysqli_real_escape_string($conn, $email);

                        $cek_user = mahasiswa_query_exists($conn, "
                            SELECT id_user FROM users
                            WHERE username = '$username_db'
                            LIMIT 1
                        ");

                        if ($cek_user) {
                            $gagal++;
                            $pesan_gagal[] = "Baris $baris: username $username sudah digunakan.";
                            continue;
                        }

                        if (!empty($email)) {
                            $email_terpakai = mahasiswa_query_one($conn, "
                                SELECT username FROM users
                                WHERE email = '$email_db'
                                LIMIT 1
                            ");

                            if ($email_terpakai) {
                                $gagal++;
                                $pesan_gagal[] = "Baris $baris: email $email sudah digunakan oleh username " . ($email_terpakai['username'] ?? '-') . ".";
                                continue;
                            }
                        }

                        $cek_nim = mahasiswa_query_exists($conn, "
                            SELECT id_mahasiswa FROM mahasiswa
                            WHERE nim = '$nim_db'
                            LIMIT 1
                        ");

                        if ($cek_nim) {
                            $gagal++;
                            $pesan_gagal[] = "Baris $baris: NIM $nim sudah digunakan.";
                            continue;
                        }

                        if (!empty($nik)) {
                            $cek_nik = mahasiswa_query_exists($conn, "
                                SELECT id_mahasiswa FROM mahasiswa
                                WHERE nik = '$nik_db'
                                LIMIT 1
                            ");

                            if ($cek_nik) {
                                $gagal++;
                                $pesan_gagal[] = "Baris $baris: NIK $nik sudah digunakan.";
                                continue;
                            }
                        }

                        $tanggal_lahir_sql = "NULL";
                        if (!empty($tanggal_lahir)) {
                            $timestamp = strtotime($tanggal_lahir);
                            if ($timestamp) {
                                $tanggal_lahir_sql = "'" . date('Y-m-d', $timestamp) . "'";
                            }
                        }

                        $tahun_lulus_sql = !empty($tahun_lulus) ? "'" . intval($tahun_lulus) . "'" : "NULL";
                        $angkatan_sql = !empty($angkatan) ? "'" . intval($angkatan) . "'" : "NULL";

                        $tanggal_masuk_sql = "NULL";
                        if (!empty($tanggal_masuk)) {
                            $timestamp = strtotime($tanggal_masuk);
                            if ($timestamp) {
                                $tanggal_masuk_sql = "'" . date('Y-m-d', $timestamp) . "'";
                            }
                        }

                        $tanggal_keluar_sql = "NULL";
                        if (!empty($tanggal_keluar)) {
                            $timestamp = strtotime($tanggal_keluar);
                            if ($timestamp) {
                                $tanggal_keluar_sql = "'" . date('Y-m-d', $timestamp) . "'";
                            }
                        }

                        $id_kelas_sql = $id_kelas > 0 ? "'$id_kelas'" : "NULL";

                        $nama_mahasiswa_db = mysqli_real_escape_string($conn, $nama_mahasiswa);
                        $jenis_kelamin_db = mysqli_real_escape_string($conn, $jenis_kelamin);
                        $tempat_lahir_db = mysqli_real_escape_string($conn, $tempat_lahir);
                        $agama_db = mysqli_real_escape_string($conn, $agama);
                        $nisn_db = mysqli_real_escape_string($conn, $nisn);
                        $npwp_db = mysqli_real_escape_string($conn, $npwp);
                        $kewarganegaraan_db = mysqli_real_escape_string($conn, $kewarganegaraan);
                        $alamat_db = mysqli_real_escape_string($conn, $alamat);
                        $kode_pos_db = mysqli_real_escape_string($conn, $kode_pos);
                        $no_hp_db = mysqli_real_escape_string($conn, $no_hp);
                        $asal_sekolah_db = mysqli_real_escape_string($conn, $asal_sekolah);
                        $nama_ayah_db = mysqli_real_escape_string($conn, $nama_ayah);
                        $nama_ibu_db = mysqli_real_escape_string($conn, $nama_ibu);
                        $pekerjaan_ayah_db = mysqli_real_escape_string($conn, $pekerjaan_ayah);
                        $pekerjaan_ibu_db = mysqli_real_escape_string($conn, $pekerjaan_ibu);
                        $penghasilan_ortu_db = mysqli_real_escape_string($conn, $penghasilan_ortu);
                        $status_mahasiswa_db = mysqli_real_escape_string($conn, $status_mahasiswa);
                        $jalur_masuk_db = mysqli_real_escape_string($conn, $jalur_masuk);

                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $email_sql = !empty($email) ? "'$email_db'" : "NULL";
                        $no_hp_sql = !empty($no_hp) ? "'$no_hp_db'" : "NULL";

                        $simpan_user = mahasiswa_execute($conn, "
                            INSERT INTO users
                            (id_role, username, password, nama_lengkap, email, no_hp, status)
                            VALUES
                            ('$id_role_mahasiswa', '$username_db', '$password_hash', '$nama_mahasiswa_db', $email_sql, $no_hp_sql, 'aktif')
                        ");

                        if (!$simpan_user) {
                            $gagal++;
                            $pesan_gagal[] = "Baris $baris: gagal membuat akun pengguna.";
                            continue;
                        }

                        $id_user_baru = mysqli_insert_id($conn);

                        $simpan_mahasiswa = mahasiswa_execute($conn, "
                            INSERT INTO mahasiswa
                            (
                                id_user,
                                id_prodi,
                                id_kelas,
                                nim,
                                nama_mahasiswa,
                                jenis_kelamin,
                                tempat_lahir,
                                tanggal_lahir,
                                agama,
                                nik,
                                nisn,
                                npwp,
                                kewarganegaraan,
                                alamat,
                                kode_pos,
                                email,
                                no_hp,
                                asal_sekolah,
                                tahun_lulus,
                                nama_ayah,
                                nama_ibu,
                                pekerjaan_ayah,
                                pekerjaan_ibu,
                                penghasilan_ortu,
                                status_mahasiswa,
                                angkatan,
                                semester,
                                jalur_masuk,
                                tanggal_masuk,
                                tanggal_keluar,
                                status
                            )
                            VALUES
                            (
                                '$id_user_baru',
                                '$id_prodi',
                                $id_kelas_sql,
                                '$nim_db',
                                '$nama_mahasiswa_db',
                                '$jenis_kelamin_db',
                                '$tempat_lahir_db',
                                $tanggal_lahir_sql,
                                '$agama_db',
                                '$nik_db',
                                '$nisn_db',
                                '$npwp_db',
                                '$kewarganegaraan_db',
                                '$alamat_db',
                                '$kode_pos_db',
                                '$email_db',
                                '$no_hp_db',
                                '$asal_sekolah_db',
                                $tahun_lulus_sql,
                                '$nama_ayah_db',
                                '$nama_ibu_db',
                                '$pekerjaan_ayah_db',
                                '$pekerjaan_ibu_db',
                                '$penghasilan_ortu_db',
                                '$status_mahasiswa_db',
                                $angkatan_sql,
                                '$semester',
                                '$jalur_masuk_db',
                                $tanggal_masuk_sql,
                                $tanggal_keluar_sql,
                                'aktif'
                            )
                        ");

                        if (!$simpan_mahasiswa) {
                            mahasiswa_execute($conn, "DELETE FROM users WHERE id_user = '$id_user_baru'");
                            $gagal++;
                            $pesan_gagal[] = "Baris $baris: gagal menyimpan data mahasiswa.";
                            continue;
                        }

                        $berhasil++;
                    }

                    mysqli_commit($conn);

                    simpan_log(
                        $conn,
                        $_SESSION['id_user'],
                        "Import data mahasiswa. Berhasil: $berhasil, Gagal: $gagal",
                        "Mahasiswa"
                    );

                    $_SESSION['import_summary_mahasiswa'] = [
                        'total' => $berhasil + $gagal,
                        'berhasil' => $berhasil,
                        'gagal' => $gagal
                    ];

                    if ($gagal > 0) {
                        $_SESSION['import_error_mahasiswa'] = $pesan_gagal;
                        set_alert("warning", "Import selesai. Berhasil: $berhasil, Gagal: $gagal.");
                    } else {
                        set_alert("success", "Import mahasiswa berhasil. Total data masuk: $berhasil.");
                    }

                    header("Location: import_mahasiswa.php");
                    exit;
                }

            } catch (Exception $e) {
                mysqli_rollback($conn);
                set_alert("error", "Gagal membaca file Excel: " . $e->getMessage());
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

    <?php if (!empty($_SESSION['import_summary_mahasiswa'])): ?>
        <?php $summary = $_SESSION['import_summary_mahasiswa']; ?>

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

        <?php unset($_SESSION['import_summary_mahasiswa']); ?>
    <?php endif; ?>

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Import Data Mahasiswa</h2>
            <p class="text-sm text-slate-500">
                Unggah file Excel untuk memasukkan data mahasiswa secara massal.
            </p>
        </div>

        <a href="data_mahasiswa.php"
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
                        Format file harus .xls atau .xlsx. Baris pertama adalah header.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="submit"
                        onclick="return confirm('Yakin ingin mengimport data mahasiswa dari file ini?')"
                        class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-file-import mr-2"></i>
                        Import Data
                    </button>

                    <a href="template_import_mahasiswa.php"
                        class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-semibold">
                        <i class="fa-solid fa-download mr-2"></i>
                        Download Template
                    </a>

                    <a href="data_mahasiswa.php"
                        class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Kembali
                    </a>
                </div>

            </form>

            <?php if (!empty($_SESSION['import_error_mahasiswa'])): ?>
                <div class="mt-6 p-4 rounded-xl bg-yellow-50 border border-yellow-200">
                    <h3 class="font-bold text-yellow-700 mb-3">Catatan Data Gagal</h3>

                    <ul class="list-disc pl-5 text-sm text-yellow-700 space-y-1">
                        <?php foreach ($_SESSION['import_error_mahasiswa'] as $error): ?>
                            <li><?= htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <?php unset($_SESSION['import_error_mahasiswa']); ?>
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
                            <td class="px-3 py-2">id_kelas</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">C</td>
                            <td class="px-3 py-2">nim</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">D</td>
                            <td class="px-3 py-2">nama_mahasiswa</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">E</td>
                            <td class="px-3 py-2">jenis_kelamin</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">F</td>
                            <td class="px-3 py-2">tempat_lahir</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">G</td>
                            <td class="px-3 py-2">tanggal_lahir</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">H</td>
                            <td class="px-3 py-2">agama</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">I</td>
                            <td class="px-3 py-2">nik</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">J</td>
                            <td class="px-3 py-2">nisn</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">K</td>
                            <td class="px-3 py-2">npwp</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">L</td>
                            <td class="px-3 py-2">kewarganegaraan</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">M</td>
                            <td class="px-3 py-2">alamat</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">N</td>
                            <td class="px-3 py-2">kode_pos</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">O</td>
                            <td class="px-3 py-2">email</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">P</td>
                            <td class="px-3 py-2">no_hp</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">Q</td>
                            <td class="px-3 py-2">asal_sekolah</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">R</td>
                            <td class="px-3 py-2">tahun_lulus</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">S</td>
                            <td class="px-3 py-2">nama_ayah</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">T</td>
                            <td class="px-3 py-2">nama_ibu</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">U</td>
                            <td class="px-3 py-2">pekerjaan_ayah</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">V</td>
                            <td class="px-3 py-2">pekerjaan_ibu</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">W</td>
                            <td class="px-3 py-2">penghasilan_ortu</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">X</td>
                            <td class="px-3 py-2">status_mahasiswa</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">Y</td>
                            <td class="px-3 py-2">angkatan</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">Z</td>
                            <td class="px-3 py-2">semester</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">AA</td>
                            <td class="px-3 py-2">jalur_masuk</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">AB</td>
                            <td class="px-3 py-2">tanggal_masuk</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">AC</td>
                            <td class="px-3 py-2">tanggal_keluar</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">AD</td>
                            <td class="px-3 py-2">username</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">AE</td>
                            <td class="px-3 py-2">password</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="text-sm text-slate-600 space-y-3">
                <p><strong>Wajib:</strong> id_prodi, nim, nama_mahasiswa, username.</p>
                <p><strong>jenis_kelamin:</strong> L atau P.</p>
                <p><strong>status_mahasiswa:</strong> aktif, cuti, nonaktif, lulus, drop out, mengundurkan diri, pindah.
                </p>
                <p><strong>Tanggal:</strong> gunakan format YYYY-MM-DD.</p>
                <p><strong>id_kelas:</strong> boleh dikosongkan atau diisi 0 jika belum masuk kelas.</p>
            </div>

        </aside>

    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
