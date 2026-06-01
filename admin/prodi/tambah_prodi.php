<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../includes/notification.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Tambah Program Studi";
$page_subtitle = "Menambahkan master data program studi";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_prodi = mysqli_real_escape_string($conn, strtoupper(trim($_POST['kode_prodi'] ?? '')));
    $nama_prodi = mysqli_real_escape_string($conn, trim($_POST['nama_prodi'] ?? ''));
    $jenjang = mysqli_real_escape_string($conn, $_POST['jenjang'] ?? 'D3');
    $gelar = mysqli_real_escape_string($conn, trim($_POST['gelar'] ?? ''));
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'aktif');

    if (empty($kode_prodi) || empty($nama_prodi)) {
        set_alert("error", "Kode prodi dan nama prodi wajib diisi.");
    } else {
        $cek = mysqli_query($conn, "SELECT id_prodi FROM prodi WHERE kode_prodi='$kode_prodi' LIMIT 1");

        if (mysqli_num_rows($cek) > 0) {
            set_alert("error", "Kode prodi sudah digunakan.");
        } else {
            $simpan = mysqli_query($conn, "
                INSERT INTO prodi (kode_prodi, nama_prodi, jenjang, gelar, status)
                VALUES ('$kode_prodi', '$nama_prodi', '$jenjang', '$gelar', '$status')
            ");

            if ($simpan) {
                simpan_log($conn, $_SESSION['id_user'], "Menambahkan program studi: $nama_prodi", "Program Studi");

                kirim_notifikasi(
                    $conn,
                    $_SESSION['id_user'],
                    "Program Studi Ditambahkan",
                    "Program studi $nama_prodi berhasil ditambahkan.",
                    "success",
                    "data_prodi.php"
                );

                set_alert("success", "Program studi berhasil ditambahkan.");
                header("Location: data_prodi.php");
                exit;
            } else {
                set_alert("error", "Program studi gagal ditambahkan.");
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
            <h2 class="text-xl font-bold text-slate-800">Tambah Program Studi</h2>
            <p class="text-sm text-slate-500">Lengkapi form berikut untuk menambahkan prodi.</p>
        </div>

        <a href="data_prodi.php" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div> -->

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
        <form method="POST" class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Kode Prodi</label>
                <input type="text" name="kode_prodi" required class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none" placeholder="MI">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Prodi</label>
                <input type="text" name="nama_prodi" required class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none" placeholder="Manajemen Informatika">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Jenjang</label>
                <select name="jenjang" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    <option value="D3">D3</option>
                    <option value="D4">D4</option>
                    <option value="S1">S1</option>
                    <option value="S2">S2</option>
                    <option value="S3">S3</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Gelar</label>
                <input type="text" name="gelar" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none" placeholder="A.Md.Kom.">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>

            <div class="lg:col-span-2 flex flex-col sm:flex-row gap-3 pt-4">
                <button type="submit" class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                    <i class="fa-solid fa-save mr-2"></i>
                    Simpan
                </button>

                <a href="data_prodi.php" class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-600 hover:bg-slate-700 text-white font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Kembali
                </a>
            </div>

        </form>
    </section>

</main>

<?php require_once "../../includes/footer.php"; ?>