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

$page_title = "Edit Kurikulum";
$page_subtitle = "Mengubah data kurikulum program studi";

$id_kurikulum = intval($_GET['id'] ?? 0);

if ($id_kurikulum <= 0) {
    set_alert("error", "ID kurikulum tidak valid.");
    header("Location: data_kurikulum.php");
    exit;
}

$data = kurikulum_query_one($conn, "
    SELECT * 
    FROM kurikulum 
    WHERE id_kurikulum = '$id_kurikulum'
    LIMIT 1
");

if (!$data) {
    set_alert("error", "Data kurikulum tidak ditemukan.");
    header("Location: data_kurikulum.php");
    exit;
}

$data_prodi = kurikulum_fetch_all($conn, "
    SELECT * 
    FROM prodi 
    WHERE status = 'aktif'
    ORDER BY nama_prodi ASC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_prodi = intval($_POST['id_prodi'] ?? 0);
    $nama_kurikulum = mysqli_real_escape_string($conn, trim($_POST['nama_kurikulum'] ?? ''));
    $tahun_kurikulum = intval($_POST['tahun_kurikulum'] ?? 0);
    $total_sks = intval($_POST['total_sks'] ?? 0);
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'aktif');

    if ($id_prodi <= 0 || empty($nama_kurikulum) || $tahun_kurikulum <= 0) {
        set_alert("error", "Program studi, nama kurikulum, dan tahun kurikulum wajib diisi.");
    } elseif ($total_sks < 0) {
        set_alert("error", "Total SKS tidak boleh bernilai negatif.");
    } else {
        $duplikat = kurikulum_query_exists($conn, "
            SELECT id_kurikulum 
            FROM kurikulum 
            WHERE id_prodi = '$id_prodi'
            AND nama_kurikulum = '$nama_kurikulum'
            AND tahun_kurikulum = '$tahun_kurikulum'
            AND id_kurikulum != '$id_kurikulum'
            LIMIT 1
        ");

        if ($duplikat === null) {
            set_alert("error", "Validasi kurikulum gagal diproses.");
        } elseif ($duplikat) {
            set_alert("error", "Kurikulum dengan prodi, nama, dan tahun tersebut sudah tersedia.");
        } else {
            $update = mysqli_query($conn, "
                UPDATE kurikulum SET
                    id_prodi = '$id_prodi',
                    nama_kurikulum = '$nama_kurikulum',
                    tahun_kurikulum = '$tahun_kurikulum',
                    total_sks = '$total_sks',
                    status = '$status'
                WHERE id_kurikulum = '$id_kurikulum'
            ");

            if ($update) {
                simpan_log(
                    $conn,
                    $_SESSION['id_user'],
                    "Mengubah kurikulum: " . $nama_kurikulum,
                    "Kurikulum"
                );

                set_alert("success", "Kurikulum berhasil diperbarui.");
                header("Location: data_kurikulum.php");
                exit;
            } else {
                set_alert("error", "Kurikulum gagal diperbarui.");
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
            <h2 class="text-xl font-bold text-slate-800">Edit Kurikulum</h2>
            <p class="text-sm text-slate-500">Perbarui data kurikulum program studi.</p>
        </div>

        <a href="data_kurikulum.php"
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
                            <option value="<?= $prodi['id_prodi']; ?>"
                                <?= $data['id_prodi'] == $prodi['id_prodi'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($prodi['nama_prodi']); ?> - <?= htmlspecialchars($prodi['jenjang']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Nama Kurikulum
                    </label>
                    <input type="text"
                           name="nama_kurikulum"
                           required
                           value="<?= htmlspecialchars($data['nama_kurikulum']); ?>"
                           placeholder="Contoh: Kurikulum OBE 2026"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Tahun Kurikulum
                    </label>
                    <input type="number"
                           name="tahun_kurikulum"
                           required
                           min="2000"
                           max="2100"
                           value="<?= htmlspecialchars($data['tahun_kurikulum']); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Total SKS
                    </label>
                    <input type="number"
                           name="total_sks"
                           min="0"
                           value="<?= htmlspecialchars($data['total_sks']); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Status
                    </label>
                    <select name="status"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="aktif" <?= $data['status'] == 'aktif' ? 'selected' : ''; ?>>
                            Aktif
                        </option>
                        <option value="nonaktif" <?= $data['status'] == 'nonaktif' ? 'selected' : ''; ?>>
                            Nonaktif
                        </option>
                    </select>
                </div>

                <div class="lg:col-span-2 flex flex-col sm:flex-row gap-3 pt-4">
                    <button type="submit"
                            class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-save mr-2"></i>
                        Update
                    </button>

                    <a href="data_kurikulum.php"
                       class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Kembali
                    </a>
                </div>

            </form>

        </div>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">
                Informasi Edit
            </h3>

            <div class="space-y-3 text-sm text-slate-600 mb-6">
                <p>
                    Perubahan kurikulum akan memengaruhi data mata kuliah yang berada di bawah kurikulum tersebut.
                </p>

                <p>
                    Pastikan program studi, tahun kurikulum, dan total SKS sudah sesuai dengan dokumen kurikulum resmi.
                </p>
            </div>

            <div class="space-y-3">
                <a href="detail_kurikulum.php?id=<?= $data['id_kurikulum']; ?>"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold">
                    <i class="fa-solid fa-eye mr-2"></i>
                    Detail Kurikulum
                </a>

                <!-- <a href="data_kurikulum.php"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold">
                    <i class="fa-solid fa-list mr-2"></i>
                    Data Kurikulum
                </a> -->
            </div>
        </aside>

    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
