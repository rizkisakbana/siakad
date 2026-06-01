<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Tambah Tahun Akademik";
$page_subtitle = "Menambahkan periode tahun akademik";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tahun = mysqli_real_escape_string($conn, trim($_POST['tahun'] ?? ''));
    $semester = mysqli_real_escape_string($conn, $_POST['semester'] ?? '');
    $tanggal_mulai = mysqli_real_escape_string($conn, $_POST['tanggal_mulai'] ?? '');
    $tanggal_selesai = mysqli_real_escape_string($conn, $_POST['tanggal_selesai'] ?? '');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'nonaktif');

    if (empty($tahun) || empty($semester) || empty($tanggal_mulai) || empty($tanggal_selesai)) {
        set_alert("error", "Tahun, semester, tanggal mulai, dan tanggal selesai wajib diisi.");
    } elseif ($tanggal_mulai > $tanggal_selesai) {
        set_alert("error", "Tanggal mulai tidak boleh lebih besar dari tanggal selesai.");
    } else {
        $cek = mysqli_query($conn, "
            SELECT id_tahun FROM tahun_akademik 
            WHERE tahun='$tahun' AND semester='$semester'
            LIMIT 1
        ");

        if (mysqli_num_rows($cek) > 0) {
            set_alert("error", "Tahun akademik dan semester tersebut sudah tersedia.");
        } else {
            if ($status == 'aktif') {
                mysqli_query($conn, "UPDATE tahun_akademik SET status='nonaktif'");
            }

            $simpan = mysqli_query($conn, "
                INSERT INTO tahun_akademik 
                (tahun, semester, tanggal_mulai, tanggal_selesai, status)
                VALUES
                ('$tahun', '$semester', '$tanggal_mulai', '$tanggal_selesai', '$status')
            ");

            if ($simpan) {
                simpan_log($conn, $_SESSION['id_user'], "Menambahkan tahun akademik: $tahun - $semester", "Tahun Akademik");
                set_alert("success", "Tahun akademik berhasil ditambahkan.");
                header("Location: data_tahun.php");
                exit;
            } else {
                set_alert("error", "Tahun akademik gagal ditambahkan.");
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
            <h2 class="text-xl font-bold text-slate-800">Tambah Tahun Akademik</h2>
            <p class="text-sm text-slate-500">Lengkapi data periode akademik.</p>
        </div>

        <a href="data_tahun.php" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div> -->

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
        <form method="POST" class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tahun Akademik</label>
                <input type="text" name="tahun" required placeholder="2025/2026"
                       class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Semester</label>
                <select name="semester" required class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    <option value="">-- Pilih Semester --</option>
                    <option value="Ganjil">Ganjil</option>
                    <option value="Genap">Genap</option>
                    <option value="Pendek">Pendek</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" required
                       class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" required
                       class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    <option value="nonaktif">Nonaktif</option>
                    <option value="aktif">Aktif</option>
                </select>

                <p class="text-xs text-slate-500 mt-2">
                    Jika memilih aktif, tahun akademik aktif sebelumnya otomatis dinonaktifkan.
                </p>
            </div>

            <div class="lg:col-span-2 flex flex-col sm:flex-row gap-3 pt-4">
                <button type="submit" class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                    <i class="fa-solid fa-save mr-2"></i>
                    Simpan
                </button>

                <a href="data_tahun.php" class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
                </a>
            </div>

        </form>
    </section>

</main>

<?php require_once "../../includes/footer.php"; ?>