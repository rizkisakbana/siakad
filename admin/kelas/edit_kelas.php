<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/kelas_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Edit Kelas";
$page_subtitle = "Mengubah master data kelas akademik";

$id_kelas = intval($_GET['id'] ?? 0);

if ($id_kelas <= 0) {
    set_alert("error", "ID kelas tidak valid.");
    header("Location: data_kelas.php");
    exit;
}

$data = kelas_query_one($conn, "
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

if (!$data) {
    set_alert("error", "Data kelas tidak ditemukan.");
    header("Location: data_kelas.php");
    exit;
}

$data_prodi = kelas_fetch_all($conn, "
    SELECT * FROM prodi
    WHERE status = 'aktif'
    ORDER BY nama_prodi ASC
");

$data_tahun = kelas_fetch_all($conn, "
    SELECT * FROM tahun_akademik
    ORDER BY status ASC, tahun DESC, semester ASC
");

$total_mahasiswa = kelas_count($conn, "
    SELECT COUNT(*) AS total 
    FROM mahasiswa 
    WHERE id_kelas = '$id_kelas'
");

$total_jadwal = kelas_count($conn, "
    SELECT COUNT(*) AS total 
    FROM jadwal_kuliah 
    WHERE id_kelas = '$id_kelas'
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_prodi = intval($_POST['id_prodi'] ?? 0);
    $id_tahun = intval($_POST['id_tahun'] ?? 0);
    $kode_kelas = mysqli_real_escape_string($conn, strtoupper(trim($_POST['kode_kelas'] ?? '')));
    $nama_kelas = mysqli_real_escape_string($conn, trim($_POST['nama_kelas'] ?? ''));
    $angkatan = mysqli_real_escape_string($conn, trim($_POST['angkatan'] ?? ''));
    $semester = intval($_POST['semester'] ?? 0);
    $kapasitas = intval($_POST['kapasitas'] ?? 0);
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'aktif');

    if ($id_prodi <= 0 || $id_tahun <= 0 || empty($kode_kelas) || empty($nama_kelas)) {
        set_alert("error", "Program studi, tahun akademik, kode kelas, dan nama kelas wajib diisi.");
    } elseif ($semester < 1 || $semester > 8) {
        set_alert("error", "Semester harus berada pada rentang 1 sampai 8.");
    } elseif ($kapasitas < 0) {
        set_alert("error", "Kapasitas tidak boleh bernilai negatif.");
    } elseif ($kapasitas < $total_mahasiswa) {
        set_alert("error", "Kapasitas tidak boleh lebih kecil dari jumlah mahasiswa yang sudah masuk kelas ini.");
    } else {
        $duplikat = kelas_query_exists($conn, "
            SELECT id_kelas 
            FROM kelas
            WHERE kode_kelas = '$kode_kelas'
            AND id_prodi = '$id_prodi'
            AND id_tahun = '$id_tahun'
            AND id_kelas != '$id_kelas'
            LIMIT 1
        ");

        if ($duplikat === null) {
            set_alert("error", "Validasi kode kelas gagal diproses.");
        } elseif ($duplikat) {
            set_alert("error", "Kode kelas sudah digunakan pada prodi dan tahun akademik tersebut.");
        } else {
            $angkatan_sql = !empty($angkatan) ? "'$angkatan'" : "NULL";

            $update = mysqli_query($conn, "
                UPDATE kelas SET
                    id_prodi = '$id_prodi',
                    id_tahun = '$id_tahun',
                    kode_kelas = '$kode_kelas',
                    nama_kelas = '$nama_kelas',
                    angkatan = $angkatan_sql,
                    semester = '$semester',
                    kapasitas = '$kapasitas',
                    status = '$status'
                WHERE id_kelas = '$id_kelas'
            ");

            if ($update) {
                simpan_log(
                    $conn,
                    $_SESSION['id_user'],
                    "Mengubah kelas: $kode_kelas - $nama_kelas",
                    "Kelas"
                );

                set_alert("success", "Data kelas berhasil diperbarui.");
                header("Location: data_kelas.php");
                exit;
            } else {
                set_alert("error", "Data kelas gagal diperbarui.");
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
            <h2 class="text-xl font-bold text-slate-800">Edit Kelas</h2>
            <p class="text-sm text-slate-500">
                Perbarui data kelas berdasarkan program studi dan tahun akademik.
            </p>
        </div>

        <a href="data_kelas.php"
           class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div> -->

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <form method="POST" class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Program Studi
                    </label>

                    <select name="id_prodi"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="">-- Pilih Program Studi --</option>

                        <?php foreach ($data_prodi as $prodi): ?>
                            <option value="<?= $prodi['id_prodi']; ?>" <?= $data['id_prodi'] == $prodi['id_prodi'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($prodi['nama_prodi']); ?> - <?= htmlspecialchars($prodi['jenjang']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Tahun Akademik
                    </label>

                    <select name="id_tahun"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="">-- Pilih Tahun Akademik --</option>

                        <?php foreach ($data_tahun as $tahun): ?>
                            <option value="<?= $tahun['id_tahun']; ?>" <?= $data['id_tahun'] == $tahun['id_tahun'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($tahun['tahun']); ?> - <?= htmlspecialchars($tahun['semester']); ?>
                                <?= $tahun['status'] == 'aktif' ? '(Aktif)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Kode Kelas
                    </label>
                    <input type="text"
                           name="kode_kelas"
                           required
                           value="<?= htmlspecialchars($data['kode_kelas'] ?? ''); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Nama Kelas
                    </label>
                    <input type="text"
                           name="nama_kelas"
                           required
                           value="<?= htmlspecialchars($data['nama_kelas'] ?? ''); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Angkatan
                    </label>
                    <input type="number"
                           name="angkatan"
                           min="2000"
                           max="2100"
                           value="<?= htmlspecialchars($data['angkatan'] ?? ''); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Semester
                    </label>

                    <select name="semester"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i; ?>" <?= intval($data['semester']) == $i ? 'selected' : ''; ?>>
                                Semester <?= $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Kapasitas
                    </label>
                    <input type="number"
                           name="kapasitas"
                           min="0"
                           value="<?= htmlspecialchars($data['kapasitas'] ?? 0); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

                    <p class="text-xs text-slate-500 mt-2">
                        Jumlah mahasiswa saat ini: <?= number_format($total_mahasiswa); ?>.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Status
                    </label>

                    <select name="status"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
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

                    <a href="data_kelas.php"
                       class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                       <i class="fa-solid fa-arrow-left mr-2"></i> 
                       Kembali
                    </a>
                </div>

            </form>

        </div>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <h3 class="text-lg font-bold text-slate-800 mb-4">
                Informasi Kelas
            </h3>

            <div class="space-y-4 text-sm text-slate-600">

                <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                    <p class="font-semibold text-blue-700 mb-1">Program Studi Saat Ini</p>
                    <p><?= htmlspecialchars($data['nama_prodi'] ?? '-'); ?> - <?= htmlspecialchars($data['jenjang'] ?? '-'); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-green-50 border border-green-100">
                    <p class="font-semibold text-green-700 mb-1">Tahun Akademik Saat Ini</p>
                    <p><?= htmlspecialchars($data['tahun'] ?? '-'); ?> - <?= htmlspecialchars($data['semester_tahun'] ?? '-'); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-orange-50 border border-orange-100">
                    <p class="font-semibold text-orange-700 mb-1">Relasi Data</p>
                    <p>
                        Mahasiswa: <strong><?= number_format($total_mahasiswa); ?></strong><br>
                        Jadwal: <strong><?= number_format($total_jadwal); ?></strong>
                    </p>
                </div>

            </div>

            <div class="mt-6 space-y-3">
                <a href="detail_kelas.php?id=<?= $data['id_kelas']; ?>"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold">
                    <i class="fa-solid fa-eye mr-2"></i>
                    Detail Kelas
                </a>

                <!-- <a href="data_kelas.php"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold">
                    <i class="fa-solid fa-list mr-2"></i>
                    Data Kelas
                </a> -->
            </div>

        </aside>

    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
