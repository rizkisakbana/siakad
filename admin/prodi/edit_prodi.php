<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Edit Program Studi";
$page_subtitle = "Mengubah master data program studi";

$id_prodi = intval($_GET['id'] ?? 0);

if ($id_prodi <= 0) {
    set_alert("error", "ID prodi tidak valid.");
    header("Location: data_prodi.php");
    exit;
}

$query = mysqli_query($conn, "SELECT * FROM prodi WHERE id_prodi='$id_prodi' LIMIT 1");

if (mysqli_num_rows($query) < 1) {
    set_alert("error", "Data prodi tidak ditemukan.");
    header("Location: data_prodi.php");
    exit;
}

$data = mysqli_fetch_assoc($query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_prodi = mysqli_real_escape_string($conn, strtoupper(trim($_POST['kode_prodi'] ?? '')));
    $nama_prodi = mysqli_real_escape_string($conn, trim($_POST['nama_prodi'] ?? ''));
    $jenjang = mysqli_real_escape_string($conn, $_POST['jenjang'] ?? 'D3');
    $gelar = mysqli_real_escape_string($conn, trim($_POST['gelar'] ?? ''));
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'aktif');

    if (empty($kode_prodi) || empty($nama_prodi)) {
        set_alert("error", "Kode prodi dan nama prodi wajib diisi.");
    } else {
        $cek = mysqli_query($conn, "
            SELECT id_prodi FROM prodi 
            WHERE kode_prodi='$kode_prodi' 
            AND id_prodi != '$id_prodi'
            LIMIT 1
        ");

        if (mysqli_num_rows($cek) > 0) {
            set_alert("error", "Kode prodi sudah digunakan oleh data lain.");
        } else {
            $update = mysqli_query($conn, "
                UPDATE prodi SET
                    kode_prodi='$kode_prodi',
                    nama_prodi='$nama_prodi',
                    jenjang='$jenjang',
                    gelar='$gelar',
                    status='$status'
                WHERE id_prodi='$id_prodi'
            ");

            if ($update) {
                simpan_log($conn, $_SESSION['id_user'], "Mengubah program studi: $nama_prodi", "Program Studi");
                set_alert("success", "Program studi berhasil diperbarui.");
                header("Location: data_prodi.php");
                exit;
            } else {
                set_alert("error", "Program studi gagal diperbarui.");
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
            <h2 class="text-xl font-bold text-slate-800">Edit Program Studi</h2>
            <p class="text-sm text-slate-500">Perbarui data program studi.</p>
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
                <input type="text" name="kode_prodi" required value="<?= htmlspecialchars($data['kode_prodi']); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Prodi</label>
                <input type="text" name="nama_prodi" required value="<?= htmlspecialchars($data['nama_prodi']); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Jenjang</label>
                <select name="jenjang" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    <?php foreach (['D3','D4','S1','S2','S3'] as $j): ?>
                        <option value="<?= $j; ?>" <?= $data['jenjang'] == $j ? 'selected' : ''; ?>><?= $j; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Gelar</label>
                <input type="text" name="gelar" value="<?= htmlspecialchars($data['gelar'] ?? ''); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    <option value="aktif" <?= $data['status'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="nonaktif" <?= $data['status'] == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                </select>
            </div>

            <div class="lg:col-span-2 flex flex-col sm:flex-row gap-3 pt-4">
                <button type="submit" class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                    <i class="fa-solid fa-save mr-2"></i>
                    Update
                </button>

                <a href="data_prodi.php" class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Kembali
                </a>
            </div>

        </form>
    </section>

</main>

<?php require_once "../../includes/footer.php"; ?>