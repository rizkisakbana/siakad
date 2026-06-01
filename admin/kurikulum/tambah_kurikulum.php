<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/kurikulum_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Tambah Kurikulum";
$page_subtitle = "Menambahkan master kurikulum program studi";

$prodi = kurikulum_fetch_all($conn, "SELECT * FROM prodi WHERE status='aktif' ORDER BY nama_prodi ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_prodi = intval($_POST['id_prodi'] ?? 0);
    $nama_kurikulum = mysqli_real_escape_string($conn, trim($_POST['nama_kurikulum'] ?? ''));
    $tahun_kurikulum = mysqli_real_escape_string($conn, $_POST['tahun_kurikulum'] ?? '');
    $total_sks = intval($_POST['total_sks'] ?? 0);
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'aktif');

    if ($id_prodi <= 0 || empty($nama_kurikulum) || empty($tahun_kurikulum)) {
        set_alert("error", "Program studi, nama kurikulum, dan tahun kurikulum wajib diisi.");
    } else {
        $duplikat = kurikulum_query_exists($conn, "
            SELECT id_kurikulum FROM kurikulum 
            WHERE id_prodi='$id_prodi' 
            AND tahun_kurikulum='$tahun_kurikulum'
            LIMIT 1
        ");

        if ($duplikat === null) {
            set_alert("error", "Validasi kurikulum gagal diproses.");
        } elseif ($duplikat) {
            set_alert("error", "Kurikulum untuk prodi dan tahun tersebut sudah tersedia.");
        } else {
            $simpan = mysqli_query($conn, "
                INSERT INTO kurikulum 
                (id_prodi, nama_kurikulum, tahun_kurikulum, total_sks, status)
                VALUES
                ('$id_prodi', '$nama_kurikulum', '$tahun_kurikulum', '$total_sks', '$status')
            ");

            if ($simpan) {
                simpan_log($conn, $_SESSION['id_user'], "Menambahkan kurikulum: $nama_kurikulum", "Kurikulum");
                set_alert("success", "Kurikulum berhasil ditambahkan.");
                header("Location: data_kurikulum.php");
                exit;
            } else {
                set_alert("error", "Kurikulum gagal ditambahkan.");
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

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Tambah Kurikulum</h2>
            <p class="text-sm text-slate-500">Lengkapi form berikut untuk menambahkan kurikulum.</p>
        </div>

        <a href="data_kurikulum.php"
           class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
        <form method="POST" class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Program Studi</label>
                <select name="id_prodi" required class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                    <option value="">-- Pilih Program Studi --</option>
                    <?php foreach ($prodi as $p): ?>
                        <option value="<?= $p['id_prodi']; ?>">
                            <?= htmlspecialchars($p['jenjang'] . ' - ' . $p['nama_prodi']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Kurikulum</label>
                <input type="text" name="nama_kurikulum" required placeholder="Kurikulum 2025"
                       class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tahun Kurikulum</label>
                <input type="number" name="tahun_kurikulum" required placeholder="2025"
                       class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Total SKS</label>
                <input type="number" name="total_sks" min="0" value="0"
                       class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
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

                <a href="data_kurikulum.php" class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold">
                    Batal
                </a>
            </div>

        </form>
    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
