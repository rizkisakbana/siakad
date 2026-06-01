<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/kelas_helper.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

cek_login();
cek_role(['super_admin', 'admin_akademik']);

/** @var mysqli $conn */

$page_title = "Tambah Peserta Kelas";
$page_subtitle = "Menambahkan mahasiswa ke dalam kelas";

$id_kelas = intval($_GET['id'] ?? 0);

if ($id_kelas <= 0) {
    set_alert("error", "ID kelas tidak valid.");
    header("Location: data_kelas.php");
    exit;
}

$kelas = kelas_query_one($conn, "
    SELECT 
        kelas.*,
        prodi.nama_prodi,
        prodi.jenjang,
        tahun_akademik.tahun,
        tahun_akademik.semester AS semester_tahun
    FROM kelas
    LEFT JOIN prodi ON kelas.id_prodi = prodi.id_prodi
    LEFT JOIN tahun_akademik ON kelas.id_tahun = tahun_akademik.id_tahun
    WHERE kelas.id_kelas = '$id_kelas'
    LIMIT 1
");

if (!$kelas) {
    set_alert("error", "Data kelas tidak ditemukan.");
    header("Location: data_kelas.php");
    exit;
}

$total_peserta = kelas_count($conn, "SELECT COUNT(*) AS total FROM mahasiswa WHERE id_kelas='$id_kelas'");

$sisa_kapasitas = intval($kelas['kapasitas']) - intval($total_peserta);
if ($sisa_kapasitas < 0)
    $sisa_kapasitas = 0;

$data_mahasiswa = kelas_fetch_all($conn, "
    SELECT 
        mahasiswa.id_mahasiswa,
        mahasiswa.nim,
        mahasiswa.nama_mahasiswa,
        mahasiswa.angkatan,
        mahasiswa.semester,
        mahasiswa.status_mahasiswa
    FROM mahasiswa
    WHERE mahasiswa.id_prodi = '{$kelas['id_prodi']}'
    AND (mahasiswa.id_kelas IS NULL OR mahasiswa.id_kelas = 0)
    ORDER BY mahasiswa.nama_mahasiswa ASC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $mode = $_POST['mode'] ?? '';

    if ($mode === 'manual') {
        $id_mahasiswa = intval($_POST['id_mahasiswa'] ?? 0);

        if ($id_mahasiswa <= 0) {
            set_alert("error", "Mahasiswa wajib dipilih.");
        } elseif ($sisa_kapasitas <= 0) {
            set_alert("warning", "Kapasitas kelas sudah penuh.");
        } else {
            $mhs = kelas_query_one($conn, "
                SELECT * FROM mahasiswa
                WHERE id_mahasiswa = '$id_mahasiswa'
                AND id_prodi = '{$kelas['id_prodi']}'
                LIMIT 1
            ");

            if (!$mhs) {
                set_alert("error", "Mahasiswa tidak ditemukan atau tidak sesuai program studi kelas.");
            } else {
                if (!empty($mhs['id_kelas']) && intval($mhs['id_kelas']) > 0) {
                    set_alert("warning", "Mahasiswa sudah terdaftar pada kelas lain.");
                } else {
                    $update = mysqli_query($conn, "
                        UPDATE mahasiswa SET
                            id_kelas = '$id_kelas'
                        WHERE id_mahasiswa = '$id_mahasiswa'
                    ");

                    if ($update) {
                        simpan_log(
                            $conn,
                            $_SESSION['id_user'],
                            "Menambahkan peserta kelas: {$mhs['nama_mahasiswa']} ke kelas {$kelas['nama_kelas']}",
                            "Kelas"
                        );

                        set_alert("success", "Mahasiswa berhasil ditambahkan ke kelas.");
                        header("Location: peserta_kelas.php?id=" . $id_kelas);
                        exit;
                    } else {
                        set_alert("error", "Mahasiswa gagal ditambahkan ke kelas.");
                    }
                }
            }
        }
    }

    if ($mode === 'import') {
        if (empty($_FILES['file_excel']['name'])) {
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
                            $nim = mysqli_real_escape_string($conn, trim($row[0] ?? ''));
                            $baris = $i + 1;

                            if (empty($nim)) {
                                $gagal++;
                                $pesan_gagal[] = "Baris $baris: NIM kosong.";
                                continue;
                            }

                            $mhs = kelas_query_one($conn, "
                                SELECT * FROM mahasiswa
                                WHERE nim = '$nim'
                                AND id_prodi = '{$kelas['id_prodi']}'
                                LIMIT 1
                            ");

                            if (!$mhs) {
                                $gagal++;
                                $pesan_gagal[] = "Baris $baris: NIM $nim tidak ditemukan atau tidak sesuai prodi kelas.";
                                continue;
                            }

                            if (!empty($mhs['id_kelas']) && intval($mhs['id_kelas']) > 0) {
                                $gagal++;
                                $pesan_gagal[] = "Baris $baris: NIM $nim sudah terdaftar pada kelas lain.";
                                continue;
                            }

                            $cek_total = kelas_count($conn, "
                                SELECT COUNT(*) AS total 
                                FROM mahasiswa 
                                WHERE id_kelas='$id_kelas'
                            ");

                            if ($cek_total >= intval($kelas['kapasitas'])) {
                                $gagal++;
                                $pesan_gagal[] = "Baris $baris: kelas sudah penuh.";
                                continue;
                            }

                            $update = mysqli_query($conn, "
                                UPDATE mahasiswa SET
                                    id_kelas = '$id_kelas'
                                WHERE id_mahasiswa = '{$mhs['id_mahasiswa']}'
                            ");

                            if ($update) {
                                $berhasil++;
                            } else {
                                $gagal++;
                                $pesan_gagal[] = "Baris $baris: gagal menambahkan NIM $nim.";
                            }
                        }

                        mysqli_commit($conn);

                        simpan_log(
                            $conn,
                            $_SESSION['id_user'],
                            "Import peserta kelas {$kelas['nama_kelas']}. Berhasil: $berhasil, Gagal: $gagal",
                            "Kelas"
                        );

                        $_SESSION['import_summary_peserta_kelas'] = [
                            'total' => $berhasil + $gagal,
                            'berhasil' => $berhasil,
                            'gagal' => $gagal
                        ];

                        if ($gagal > 0) {
                            $_SESSION['import_error_peserta_kelas'] = $pesan_gagal;
                            set_alert("warning", "Import selesai. Berhasil: $berhasil, Gagal: $gagal.");
                        } else {
                            set_alert("success", "Import peserta kelas berhasil. Total data masuk: $berhasil.");
                        }

                        header("Location: tambah_peserta_kelas.php?id=" . $id_kelas);
                        exit;
                    }

                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    set_alert("error", "Gagal membaca file Excel: " . $e->getMessage());
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

    <?php if (!empty($_SESSION['import_summary_peserta_kelas'])): ?>
        <?php $summary = $_SESSION['import_summary_peserta_kelas']; ?>

        <section class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Total Data Dibaca</p>
                <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($summary['total']); ?></h2>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Berhasil Import</p>
                <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($summary['berhasil']); ?></h2>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500">Gagal Import</p>
                <h2 class="text-3xl font-bold text-red-700 mt-2"><?= number_format($summary['gagal']); ?></h2>
            </div>
        </section>

        <?php unset($_SESSION['import_summary_peserta_kelas']); ?>
    <?php endif; ?>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Tambah Peserta Kelas</h2>
            <p class="text-sm text-slate-500">
                <?= htmlspecialchars($kelas['nama_kelas']); ?> •
                <?= htmlspecialchars($kelas['nama_prodi'] ?? '-'); ?> •
                <?= htmlspecialchars($kelas['tahun'] ?? '-'); ?> -
                <?= htmlspecialchars($kelas['semester_tahun'] ?? '-'); ?>
            </p>
        </div>

        <a href="peserta_kelas.php?id=<?= $id_kelas; ?>"
            class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div>

    <section class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Peserta Saat Ini</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($total_peserta); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Kapasitas</p>
            <h2 class="text-3xl font-bold text-purple-700 mt-2"><?= number_format($kelas['kapasitas']); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Sisa Kapasitas</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($sisa_kapasitas); ?></h2>
        </div>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-1 gap-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Tambah Satu Persatu</h3>
            <p class="text-sm text-slate-500 mb-4">
                Kelas: <?= htmlspecialchars($kelas['nama_kelas']); ?> •
                Prodi: <?= htmlspecialchars($kelas['nama_prodi'] ?? '-'); ?> •
                Tahun: <?= htmlspecialchars($kelas['tahun'] ?? '-'); ?> -
                <?= htmlspecialchars($kelas['semester_tahun'] ?? '-'); ?>
            </p>

            <form method="POST" class="space-y-5">
                <input type="hidden" name="mode" value="manual">

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Pilih Mahasiswa</label>
                    <select name="id_mahasiswa" required
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="">-- Pilih Mahasiswa --</option>
                        <?php if (!empty($data_mahasiswa)): ?>
                            <?php foreach ($data_mahasiswa as $mhs): ?>
                                <option value="<?= $mhs['id_mahasiswa']; ?>">
                                    <?= htmlspecialchars($mhs['nim']); ?> - <?= htmlspecialchars($mhs['nama_mahasiswa']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class="text-xs text-slate-500 mt-2">
                        Hanya menampilkan mahasiswa satu prodi yang belum memiliki kelas.
                    </p>
                </div>
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-user-plus mr-2"></i>
                        Tambahkan
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Import Massal Peserta</h3>
            <p class="text-sm text-slate-500 mb-4">
                Kelas: <?= htmlspecialchars($kelas['nama_kelas']); ?> •
                Prodi: <?= htmlspecialchars($kelas['nama_prodi'] ?? '-'); ?> •
                Tahun: <?= htmlspecialchars($kelas['tahun'] ?? '-'); ?> -
                <?= htmlspecialchars($kelas['semester_tahun'] ?? '-'); ?>
            </p>

            <form method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="mode" value="import">

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">File Excel</label>
                    <input type="file" name="file_excel" accept=".xls,.xlsx" required
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    <p class="text-xs text-slate-500 mt-2">
                        Kolom A berisi NIM. Baris pertama adalah header.
                    </p>
                </div>
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center gap-3">
                    <button type="submit" onclick="return confirm('Yakin ingin import peserta kelas dari file ini?')"
                        class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                        <i class="fa-solid fa-file-import mr-2"></i>
                        Import Peserta
                    </button>

                    <a href="template_import_peserta.php?id=<?= $id_kelas; ?>"
                        class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-semibold">
                        <i class="fa-solid fa-download mr-2"></i>
                        Download Template
                    </a>

                    <a href="peserta_kelas.php?id=<?= $id_kelas; ?>"
                        class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Kembali
                    </a>

                </div>
            </form>

            <?php if (!empty($_SESSION['import_error_peserta_kelas'])): ?>
                <div class="mt-6 p-4 rounded-xl bg-yellow-50 border border-yellow-200">
                    <h3 class="font-bold text-yellow-700 mb-3">Catatan Data Gagal</h3>

                    <ul class="list-disc pl-5 text-sm text-yellow-700 space-y-1">
                        <?php foreach ($_SESSION['import_error_peserta_kelas'] as $error): ?>
                            <li><?= htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <?php unset($_SESSION['import_error_peserta_kelas']); ?>
            <?php endif; ?>
        </div>

    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
